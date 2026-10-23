<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catatan data telemetri yang ditolak validasi (Tabel 3.7 naskah).
 */
class TelemetryRejection extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'device_id',
        'payload',
        'errors',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'errors' => 'array',
        ];
    }
}
