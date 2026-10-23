<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Node;
use App\Models\SensorLog;
use App\Models\TelemetryRejection;
use App\Support\AirQualityClassifier;
use App\Support\Recommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TelemetryApiController extends Controller
{
    /**
     * Format timestamp perangkat (naskah 3.9.1).
     */
    private const TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    /**
     * Kunci payload yang disimpan saat data ditolak (format utama + format lama).
     */
    private const REJECTION_KEYS = [
        'device_id', 'timestamp', 'co2_ppm', 'nh3_ppm', 'co_ppm', 'h2_ppm',
        'node_id', 'co2', 'nh3', 'co', 'h2',
    ];

    /**
     * Panjang maksimum tiap nilai payload yang disimpan saat data ditolak.
     */
    private const REJECTION_VALUE_LENGTH = 255;

    /**
     * Panjang maksimum isi mentah JSON rusak yang disimpan.
     */
    private const REJECTION_RAW_LENGTH = 1000;

    /**
     * Pesan validasi berbahasa Indonesia (juga tampil di kolom Galat halaman Perangkat).
     */
    private const VALIDATION_MESSAGES = [
        'required' => ':attribute wajib diisi.',
        'string' => ':attribute harus berupa teks.',
        'numeric' => ':attribute harus berupa angka.',
        'min' => ':attribute minimal :min.',
        'max' => ':attribute maksimal :max.',
        'date_format' => ':attribute harus berformat :format.',
        'device_id.exists' => ':attribute tidak terdaftar atau tidak aktif.',
    ];

    /**
     * Nama field yang mudah dibaca pada pesan validasi.
     */
    private const VALIDATION_ATTRIBUTES = [
        'device_id' => 'device_id',
        'timestamp' => 'timestamp',
        'co2_ppm' => 'co2_ppm (CO₂)',
        'nh3_ppm' => 'nh3_ppm (NH₃)',
        'co_ppm' => 'co_ppm (CO)',
        'h2_ppm' => 'h2_ppm (H₂)',
    ];

    /**
     * Terima data telemetri dari perangkat IoT.
     *
     * Format utama: device_id, timestamp, co2_ppm, nh3_ppm, co_ppm, h2_ppm.
     * Data yang gagal validasi dijawab 422 dan dicatat di telemetry_rejections.
     */
    public function store(Request $request): JsonResponse
    {
        if ($unauthorized = $this->rejectInvalidApiKey($request)) {
            return $unauthorized;
        }

        if ($request->isJson() && ! json_validate($request->getContent())) {
            return $this->rejectPayload(
                ['_raw' => mb_substr(mb_scrub($request->getContent(), 'UTF-8'), 0, self::REJECTION_RAW_LENGTH)],
                ['payload' => ['Payload bukan JSON yang valid.']],
            );
        }

        $input = $this->normalizeLegacyPayload($request->all());

        $validator = Validator::make($input, [
            'device_id' => ['required', 'string', Rule::exists('nodes', 'code')->where('is_active', true)],
            'timestamp' => 'required|date_format:'.self::TIMESTAMP_FORMAT,
            'co2_ppm' => 'required|numeric|min:0|max:999999.99',
            'nh3_ppm' => 'required|numeric|min:0|max:999999.99',
            'co_ppm' => 'required|numeric|min:0|max:999999.99',
            'h2_ppm' => 'required|numeric|min:0|max:999999.99',
        ], self::VALIDATION_MESSAGES, self::VALIDATION_ATTRIBUTES);

        if ($validator->fails()) {
            return $this->rejectPayload($this->boundedPayload($request->all()), $validator->errors()->toArray());
        }

        $node = Node::where('code', $input['device_id'])->firstOrFail();

        // Dibulatkan 2 desimal (sesuai kolom decimal) sebelum klasifikasi agar
        // kategori konsisten dengan nilai yang tersimpan.
        $co2 = round((float) $input['co2_ppm'], 2);
        $nh3 = round((float) $input['nh3_ppm'], 2);
        $co = round((float) $input['co_ppm'], 2);
        $h2 = round((float) $input['h2_ppm'], 2);

        $result = AirQualityClassifier::classify($co2, $nh3, $co, $h2);
        $recommendation = Recommendation::forCategory($result['overall']);

        $log = SensorLog::create([
            'node_id' => $node->id,
            'co2' => $co2,
            'nh3' => $nh3,
            'co' => $co,
            'h2' => $h2,
            'recorded_at' => Carbon::createFromFormat(self::TIMESTAMP_FORMAT, $input['timestamp']),
            'co2_category' => $result['categories']['co2'],
            'nh3_category' => $result['categories']['nh3'],
            'co_category' => $result['categories']['co'],
            'h2_category' => $result['categories']['h2'],
            'overall_category' => $result['overall'],
            'dominant_gases' => $result['dominant'] === [] ? null : implode(',', $result['dominant']),
            'recommendation_level' => $recommendation['level'],
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $log->id,
                'device_id' => $node->code,
                'recorded_at' => $log->recorded_at->format(self::TIMESTAMP_FORMAT),
                'categories' => $log->categories(),
                'overall_category' => $log->overall_category,
                'dominant_gases' => $log->dominantGases(),
                'recommendation' => [
                    'level' => $recommendation['level'],
                    'text' => $recommendation['text'],
                ],
            ],
        ], 201);
    }

    /**
     * Pembacaan terakhir untuk pemeriksaan cepat.
     */
    public function index(Request $request): JsonResponse
    {
        if ($unauthorized = $this->rejectInvalidApiKey($request)) {
            return $unauthorized;
        }

        return response()->json([
            'status' => 'success',
            'recent_logs' => SensorLog::latest()->take(10)->get(),
        ]);
    }

    /**
     * Petakan format lama (node_id, co2, nh3, co, h2) ke format naskah.
     * Format lama tidak membawa timestamp sehingga memakai waktu server; field
     * QoS lama (rssi, seq_number, hop_count, topology_type) tidak dipakai.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeLegacyPayload(array $input): array
    {
        if (array_key_exists('device_id', $input) || ! array_key_exists('node_id', $input)) {
            return $input;
        }

        if (is_scalar($input['node_id'])) {
            $code = Node::whereKey($input['node_id'])->value('code');

            if ($code !== null) {
                $input['device_id'] = $code;
            }
        }

        $input['timestamp'] = now()->format(self::TIMESTAMP_FORMAT);

        foreach (['co2', 'nh3', 'co', 'h2'] as $gas) {
            if (! array_key_exists("{$gas}_ppm", $input) && array_key_exists($gas, $input)) {
                $input["{$gas}_ppm"] = $input[$gas];
            }
        }

        return $input;
    }

    /**
     * Batasi payload yang disimpan saat ditolak: hanya kunci yang dikenal, dan
     * tiap nilai dipotong agar permintaan berukuran besar tidak membanjiri basis data/log.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function boundedPayload(array $payload): array
    {
        $bounded = [];

        foreach (self::REJECTION_KEYS as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];

            if (! is_scalar($value) && $value !== null) {
                $value = (string) json_encode($value, JSON_PARTIAL_OUTPUT_ON_ERROR);
            }

            $bounded[$key] = is_string($value)
                ? mb_substr(mb_scrub($value, 'UTF-8'), 0, self::REJECTION_VALUE_LENGTH)
                : $value;
        }

        return $bounded;
    }

    /**
     * Catat data yang ditolak (tabel + log channel telemetry) lalu jawab 422.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, list<string>>  $errors
     */
    private function rejectPayload(array $payload, array $errors): JsonResponse
    {
        $deviceId = isset($payload['device_id']) && is_string($payload['device_id'])
            ? mb_substr($payload['device_id'], 0, 255)
            : null;

        TelemetryRejection::create([
            'device_id' => $deviceId,
            'payload' => $payload,
            'errors' => $errors,
        ]);

        Log::channel('telemetry')->warning('Data telemetri ditolak.', [
            'device_id' => $deviceId,
            'payload' => $payload,
            'errors' => $errors,
        ]);

        return response()->json([
            'status' => 'error',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Validasi header X-Api-Key jika TELEMETRY_API_KEY dikonfigurasi.
     * Mengembalikan respons 401 bila kunci tidak cocok, atau null bila lolos.
     */
    private function rejectInvalidApiKey(Request $request): ?JsonResponse
    {
        $configuredKey = (string) config('services.telemetry.api_key');

        if ($configuredKey === '') {
            return null;
        }

        $providedKey = (string) $request->header('X-Api-Key', '');

        if (hash_equals($configuredKey, $providedKey)) {
            return null;
        }

        return response()->json([
            'status' => 'error',
            'message' => 'API key tidak valid atau tidak disertakan.',
        ], 401);
    }
}
