<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Node belongs to a Room
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Node has many sensor telemetry logs
     */
    public function sensorLogs()
    {
        return $this->hasMany(SensorLog::class, 'node_id', 'id');
    }

    /**
     * Get latest telemetry log for this node
     */
    public function latestLog()
    {
        return $this->hasOne(SensorLog::class, 'node_id', 'id')->latestOfMany();
    }
}
