<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu data uji topologi star/mesh. Disimpan terpisah dari sensor_logs.
 */
class TopologyTestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'topology',
        'recorded_at',
        'co2',
        'nh3',
        'co',
        'h2',
        'co2_category',
        'nh3_category',
        'co_category',
        'h2_category',
        'overall_category',
        'dominant_gases',
        'recommendation_level',
        'rssi',
        'seq_number',
        'hop_count',
        'delay_ms',
        'payload_bytes',
    ];

    protected $casts = [
        'co2' => 'float',
        'nh3' => 'float',
        'co' => 'float',
        'h2' => 'float',
        'recorded_at' => 'datetime',
        'rssi' => 'integer',
        'seq_number' => 'integer',
        'hop_count' => 'integer',
        'delay_ms' => 'integer',
        'payload_bytes' => 'integer',
    ];

    /**
     * Gas penyebab utama (kategori terburuk); kosong bila seluruh gas Baik.
     *
     * @return list<string>
     */
    public function dominantGases(): array
    {
        return $this->dominant_gases === null || $this->dominant_gases === ''
            ? []
            : explode(',', $this->dominant_gases);
    }
}
