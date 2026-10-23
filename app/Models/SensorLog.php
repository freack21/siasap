<?php

namespace App\Models;

use App\Support\Recommendation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'node_id',
        'co2',
        'nh3',
        'co',
        'h2',
        'recorded_at',
        'co2_category',
        'nh3_category',
        'co_category',
        'h2_category',
        'overall_category',
        'dominant_gases',
        'recommendation_level',
    ];

    protected $casts = [
        'co2' => 'float',
        'nh3' => 'float',
        'co' => 'float',
        'h2' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id', 'id');
    }

    /**
     * Kategori tiap gas hasil klasifikasi saat data diterima.
     *
     * @return array{co2: string, nh3: string, co: string, h2: string}
     */
    public function categories(): array
    {
        return [
            'co2' => $this->co2_category,
            'nh3' => $this->nh3_category,
            'co' => $this->co_category,
            'h2' => $this->h2_category,
        ];
    }

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

    /**
     * Rekomendasi tindakan untuk kategori keseluruhan pembacaan ini.
     *
     * @return array{category: string, level: string, text: string, strategies: list<string>}
     */
    public function recommendation(): array
    {
        return Recommendation::forCategory($this->overall_category);
    }
}
