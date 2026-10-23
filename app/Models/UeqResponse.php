<?php

namespace App\Models;

use Database\Factories\UeqResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UeqResponse extends Model
{
    /** @use HasFactory<UeqResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'respondent_name',
        'answers',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return ['answers' => 'array'];
    }

    public static function hashIp(?string $ip): ?string
    {
        return $ip ? hash('sha256', $ip.config('app.key')) : null;
    }
}
