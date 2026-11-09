<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TopologyTestDevice;
use App\Models\TopologyTestLog;
use App\Support\AirQualityClassifier;
use App\Support\Recommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Endpoint tersembunyi untuk uji topologi star dan mesh. Payload sama dengan
 * POST /api/telemetry (ditambah metrik jaringan opsional), tetapi data disimpan
 * di topology_test_logs sehingga tidak tercampur dengan data SIMKU. Setiap paket
 * dicatat delay-nya (waktu terima server dikurangi waktu kirim perangkat) dan
 * ukuran payload-nya untuk menghitung delay, jitter, dan throughput.
 */
class TopologyTestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $receivedAt = now();

        if ($unauthorized = TelemetryApiController::rejectInvalidApiKey($request)) {
            return $unauthorized;
        }

        if ($request->isJson() && ! json_validate($request->getContent())) {
            return $this->reject(['payload' => ['Payload bukan JSON yang valid.']]);
        }

        $validator = Validator::make($request->all(), [
            'device_id' => ['required', 'string', Rule::in(array_keys(config('topology_test.devices')))],
            'timestamp' => 'required|date_format:'.TelemetryApiController::TIMESTAMP_FORMAT,
            'timestamp_ms' => 'nullable|integer|between:0,999',
            'co2_ppm' => 'required|numeric|min:0|max:999999.99',
            'nh3_ppm' => 'required|numeric|min:0|max:999999.99',
            'co_ppm' => 'required|numeric|min:0|max:999999.99',
            'h2_ppm' => 'required|numeric|min:0|max:999999.99',
            'rssi' => 'nullable|integer|between:-127,0',
            'seq_number' => 'nullable|integer|min:1|max:4294967295',
            'hop_count' => 'nullable|integer|between:1,10',
            'topology_type' => ['nullable', Rule::in(config('topology_test.topologies'))],
        ], [
            ...TelemetryApiController::VALIDATION_MESSAGES,
            'device_id.in' => ':attribute bukan perangkat uji topologi.',
            'in' => ':attribute tidak dikenal.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'between' => ':attribute harus di antara :min dan :max.',
        ], TelemetryApiController::VALIDATION_ATTRIBUTES);

        if ($validator->fails()) {
            return $this->reject($validator->errors()->toArray());
        }

        $input = $validator->validated();

        $co2 = round((float) $input['co2_ppm'], 2);
        $nh3 = round((float) $input['nh3_ppm'], 2);
        $co = round((float) $input['co_ppm'], 2);
        $h2 = round((float) $input['h2_ppm'], 2);

        $result = AirQualityClassifier::classify($co2, $nh3, $co, $h2);
        $recommendation = Recommendation::forCategory($result['overall']);

        // Waktu kirim perangkat = timestamp + timestamp_ms (opsional). Tanpa timestamp_ms,
        // delay hanya berketelitian detik.
        $sentAt = Carbon::createFromFormat(TelemetryApiController::TIMESTAMP_FORMAT, $input['timestamp'])
            ->microsecond(0)
            ->addMilliseconds((int) ($input['timestamp_ms'] ?? 0));

        $log = TopologyTestLog::create([
            'device_id' => $input['device_id'],
            'topology' => $input['topology_type'] ?? null,
            'recorded_at' => Carbon::createFromFormat(TelemetryApiController::TIMESTAMP_FORMAT, $input['timestamp']),
            'co2' => $co2,
            'nh3' => $nh3,
            'co' => $co,
            'h2' => $h2,
            'co2_category' => $result['categories']['co2'],
            'nh3_category' => $result['categories']['nh3'],
            'co_category' => $result['categories']['co'],
            'h2_category' => $result['categories']['h2'],
            'overall_category' => $result['overall'],
            'dominant_gases' => $result['dominant'] === [] ? null : implode(',', $result['dominant']),
            'recommendation_level' => $recommendation['level'],
            'rssi' => $input['rssi'] ?? null,
            'seq_number' => $input['seq_number'] ?? null,
            'hop_count' => $input['hop_count'] ?? null,
            'delay_ms' => (int) round($receivedAt->getPreciseTimestamp(3) - $sentAt->getPreciseTimestamp(3)),
            'payload_bytes' => strlen($request->getContent()),
            'source_ip' => $request->ip(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $log->id,
                'device_id' => $log->device_id,
                'recorded_at' => $log->recorded_at->format(TelemetryApiController::TIMESTAMP_FORMAT),
                'categories' => [
                    'co2' => $log->co2_category,
                    'nh3' => $log->nh3_category,
                    'co' => $log->co_category,
                    'h2' => $log->h2_category,
                ],
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
     * Handshake perangkat sebelum mengirim data: perangkat melaporkan IP, MAC, dan topologinya.
     * Server menyimpan IP tersebut beserta IP yang terlihat dari sisi server, lalu menjawab
     * dengan waktu server.
     */
    public function handshake(Request $request): JsonResponse
    {
        if ($unauthorized = TelemetryApiController::rejectInvalidApiKey($request)) {
            return $unauthorized;
        }

        $validator = Validator::make($request->all(), [
            'device_id' => ['required', 'string', Rule::in(array_keys(config('topology_test.devices')))],
            'ip' => 'required|ipv4',
            'mac' => ['nullable', 'string', 'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/'],
            'topology_type' => ['nullable', Rule::in(config('topology_test.topologies'))],
            'rssi' => 'nullable|integer|between:-127,0',
        ], [
            ...TelemetryApiController::VALIDATION_MESSAGES,
            'device_id.in' => ':attribute bukan perangkat uji topologi.',
            'in' => ':attribute tidak dikenal.',
            'ipv4' => ':attribute harus berupa alamat IPv4.',
            'regex' => ':attribute harus berformat AA:BB:CC:DD:EE:FF.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'between' => ':attribute harus di antara :min dan :max.',
        ]);

        if ($validator->fails()) {
            return $this->reject($validator->errors()->toArray());
        }

        $input = $validator->validated();
        $device = TopologyTestDevice::firstOrNew(['device_id' => $input['device_id']]);
        $device->fill([
            'ip' => $input['ip'],
            'remote_ip' => $request->ip(),
            'mac' => isset($input['mac']) ? strtoupper($input['mac']) : null,
            'topology' => $input['topology_type'] ?? null,
            'rssi' => $input['rssi'] ?? null,
            'handshake_count' => $device->handshake_count + 1,
            'last_handshake_at' => now(),
        ])->save();

        return response()->json([
            'status' => 'success',
            'data' => [
                'device_id' => $device->device_id,
                'ip' => $device->ip,
                'remote_ip' => $device->remote_ip,
                'handshake_count' => $device->handshake_count,
                'server_time' => now()->format(TelemetryApiController::TIMESTAMP_FORMAT),
            ],
        ]);
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private function reject(array $errors): JsonResponse
    {
        return response()->json(['status' => 'error', 'errors' => $errors], 422);
    }
}
