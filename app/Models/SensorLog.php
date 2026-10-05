<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'testing_session_id',
        'node_id',
        'co2',
        'nh3',
        'co',
        'h2',
        'ku_value',
        'ku_status',
        'rssi',
        'seq_number',
        'hop_count',
        'topology_type',
    ];

    protected $casts = [
        'co2' => 'float',
        'nh3' => 'float',
        'co' => 'float',
        'h2' => 'float',
        'ku_value' => 'float',
        'rssi' => 'integer',
        'seq_number' => 'integer',
        'hop_count' => 'integer',
    ];

    public function testingSession()
    {
        return $this->belongsTo(TestingSession::class);
    }

    public function node()
    {
        return $this->belongsTo(Node::class, 'node_id', 'id');
    }

    /**
     * Calculate Air Quality Value (KU) as max of 4 gas parameters
     */
    public static function computeKuValue(float $co2, float $nh3, float $co, float $h2): float
    {
        return max($co2, $nh3, $co, $h2);
    }

    /**
     * Determine Air Quality Status Category based on KU Value
     */
    public static function computeKuStatus(float $kuValue): string
    {
        if ($kuValue <= 50) {
            return 'Baik';
        } elseif ($kuValue <= 150) {
            return 'Sedang';
        } elseif ($kuValue <= 300) {
            return 'Tidak Sehat';
        } else {
            return 'Berbahaya';
        }
    }

    /**
     * Get Tailwind Badge style for KU Status
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->ku_status) {
            'Baik' => [
                'bg' => 'bg-emerald-500/15 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border-emerald-500/30',
                'dot' => 'bg-emerald-500 animate-pulse',
                'icon' => 'heroicon-o-check-circle',
            ],
            'Sedang' => [
                'bg' => 'bg-amber-500/15 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 border-amber-500/30',
                'dot' => 'bg-amber-500',
                'icon' => 'heroicon-o-exclamation-triangle',
            ],
            'Tidak Sehat' => [
                'bg' => 'bg-orange-500/15 dark:bg-orange-500/20 text-orange-700 dark:text-orange-400 border-orange-500/30',
                'dot' => 'bg-orange-500',
                'icon' => 'heroicon-o-exclamation-circle',
            ],
            'Berbahaya' => [
                'bg' => 'bg-rose-500/15 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400 border-rose-500/30',
                'dot' => 'bg-rose-500 animate-ping',
                'icon' => 'heroicon-o-x-circle',
            ],
            default => [
                'bg' => 'bg-slate-500/15 text-slate-700 dark:text-slate-400 border-slate-500/30',
                'dot' => 'bg-slate-500',
                'icon' => 'heroicon-o-information-circle',
            ],
        };
    }
}
