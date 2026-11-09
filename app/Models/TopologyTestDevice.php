<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Handshake terakhir satu perangkat uji topologi (IP perangkat dan IP yang terlihat server).
 */
class TopologyTestDevice extends Model
{
    protected $fillable = [
        'device_id',
        'ip',
        'remote_ip',
        'mac',
        'topology',
        'rssi',
        'handshake_count',
        'last_handshake_at',
    ];

    protected $casts = [
        'rssi' => 'integer',
        'handshake_count' => 'integer',
        'last_handshake_at' => 'datetime',
    ];
}
