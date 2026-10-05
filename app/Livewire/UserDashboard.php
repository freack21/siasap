<?php

namespace App\Livewire;

use App\Models\SensorLog;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pemantauan Kualitas Udara — SIASAP')]
class UserDashboard extends Component
{
    /**
     * Poll telemetry data every 3 seconds for real-time updates
     */
    public function refreshData()
    {
        // Auto refresh via Livewire polling
    }

    public function render()
    {
        $nodes = \App\Models\Node::with('room')->where('is_active', true)->get();

        $areasData = [];
        $maxKuOverall = 0;
        $worstStatus = 'Baik';

        foreach ($nodes as $node) {
            $latest = SensorLog::where('node_id', $node->id)->latest()->first();
            $isOnline = $latest && $latest->created_at->diffInSeconds(now()) <= 30;

            if ($latest && $latest->ku_value > $maxKuOverall) {
                $maxKuOverall = $latest->ku_value;
                $worstStatus = $latest->ku_status;
            }

            $areaName = $node->room ? $node->room->name : $node->name;

            $areasData[$node->id] = [
                'area_id' => $node->id,
                'name' => $areaName,
                'node_name' => $node->name,
                'room_code' => $node->room?->code,
                'latest' => $latest,
                'is_online' => $isOnline,
            ];
        }

        // Health Advice based on worst status
        $healthAdvice = match ($worstStatus) {
            'Baik' => [
                'title' => 'Udara Sangat Bersih & Segar!',
                'desc' => 'Kondisi lingkungan ideal. Aman untuk semua aktivitas luar ruangan maupun dalam ruangan.',
                'color' => 'emerald',
                'badge' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                'icon' => 'heroicon-o-sparkles',
            ],
            'Sedang' => [
                'title' => 'Kualitas Udara Cukup Baik',
                'desc' => 'Tingkat polutan masih dalam batas wajar. Kelompok sensitif disarankan tetap memperhatikan ventilasi.',
                'color' => 'amber',
                'badge' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                'icon' => 'heroicon-o-exclamation-triangle',
            ],
            'Tidak Sehat' => [
                'title' => 'Kualitas Udara Kurang Baik (Waspada)',
                'desc' => 'Terdapat peningkatan konsentrasi gas. Disarankan menggunakan masker saat berada di lokasi terpolusi.',
                'color' => 'orange',
                'badge' => 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/20',
                'icon' => 'heroicon-o-exclamation-circle',
            ],
            'Berbahaya' => [
                'title' => 'PERINGATAN BAHAYA KUALITAS UDARA!',
                'desc' => 'Konsentrasi gas berlebih terdeteksi. Segera gunakan masker N95, evakuasi area berisiko, dan nyalakan exhaust fan.',
                'color' => 'rose',
                'badge' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                'icon' => 'heroicon-o-shield-exclamation',
            ],
            default => [
                'title' => 'Kualitas Udara Normal',
                'desc' => 'Sistem aktif memantau kondisi lingkungan ruangan.',
                'color' => 'slate',
                'badge' => 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
                'icon' => 'heroicon-o-information-circle',
            ],
        };

        return view('livewire.user-dashboard', [
            'areasData' => $areasData,
            'maxKuOverall' => $maxKuOverall,
            'worstStatus' => $worstStatus,
            'healthAdvice' => $healthAdvice,
        ]);
    }
}
