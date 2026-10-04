<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'topology_type',
        'status',
        'description',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function sensorLogs()
    {
        return $this->hasMany(SensorLog::class);
    }

    /**
     * Scope active session
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
