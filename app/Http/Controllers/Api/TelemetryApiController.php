<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorLog;
use App\Models\TestingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TelemetryApiController extends Controller
{
    /**
     * Store telemetry log from ESP32 node
     */
    public function store(Request $request): JsonResponse
    {
        if ($unauthorized = $this->rejectInvalidApiKey($request)) {
            return $unauthorized;
        }

        // Support fallback from 'node' parameter (e.g. "NODE_1" or 1) to 'node_id'
        if ($request->has('node') && !$request->has('node_id')) {
            $parsedNode = (int) filter_var($request->input('node'), FILTER_SANITIZE_NUMBER_INT);
            if ($parsedNode > 0) {
                $request->merge(['node_id' => $parsedNode]);
            }
        }

        // seq_number dan hop_count wajib dikirim node (tidak boleh dipalsukan),
        // rssi boleh kosong dan akan disimpan sebagai NULL.
        $validator = Validator::make($request->all(), [
            'node_id' => 'required|integer|exists:nodes,id',
            'co2' => 'required|numeric|min:0',
            'nh3' => 'required|numeric|min:0',
            'co' => 'required|numeric|min:0',
            'h2' => 'required|numeric|min:0',
            'rssi' => 'nullable|integer|between:-120,0',
            'seq_number' => 'required|integer|min:1',
            'hop_count' => 'required|integer|min:1',
            'topology_type' => 'nullable|in:star,mesh',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get currently active testing session
        $activeSession = TestingSession::active()->latest()->first();

        $topologyType = $activeSession
            ? $activeSession->topology_type
            : ($request->input('topology_type') ?? 'star');

        $co2 = (float) $request->input('co2');
        $nh3 = (float) $request->input('nh3');
        $co  = (float) $request->input('co');
        $h2  = (float) $request->input('h2');

        $ku = SensorLog::computeKu($co2, $nh3, $co, $h2);

        $log = SensorLog::create([
            'testing_session_id' => $activeSession?->id,
            'node_id' => (int) $request->input('node_id'),
            'co2' => $co2,
            'nh3' => $nh3,
            'co' => $co,
            'h2' => $h2,
            'ku_value' => $ku['value'],
            'ku_status' => $ku['status'],
            'ku_dominant' => $ku['dominant'],
            'rssi' => $request->filled('rssi') ? (int) $request->input('rssi') : null,
            'seq_number' => (int) $request->input('seq_number'),
            'hop_count' => (int) $request->input('hop_count'),
            'topology_type' => $topologyType,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Telemetry log stored successfully',
            'data' => [
                'id' => $log->id,
                'node_id' => $log->node_id,
                'ku_value' => $log->ku_value,
                'ku_status' => $log->ku_status,
                'ku_dominant' => $log->ku_dominant,
                'topology_type' => $log->topology_type,
                'session_id' => $log->testing_session_id,
                'created_at' => $log->created_at->toIso8601String(),
            ]
        ], 201);
    }

    /**
     * Get recent status API for edge inspection
     */
    public function index(Request $request): JsonResponse
    {
        if ($unauthorized = $this->rejectInvalidApiKey($request)) {
            return $unauthorized;
        }

        $activeSession = TestingSession::active()->latest()->first();
        $latestLogs = SensorLog::latest()->take(10)->get();

        return response()->json([
            'status' => 'success',
            'active_session' => $activeSession,
            'recent_logs' => $latestLogs,
        ]);
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
