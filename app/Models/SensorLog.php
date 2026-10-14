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
        'ku_dominant',
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
     * Daftar parameter gas yang dihitung sub-indeksnya (urutan = prioritas
     * saat terjadi nilai sub-indeks yang sama).
     */
    public const GASES = ['co2', 'nh3', 'co', 'h2'];

    /**
     * Hitung sub-indeks (0–500) satu parameter gas dengan interpolasi linier
     * sepotong-sepotong pada breakpoint di config/air_quality.php.
     * Konsentrasi di atas breakpoint terakhir dibatasi pada indeks maksimum.
     */
    public static function computeSubIndex(string $gas, float $ppm): float
    {
        $breakpoints = config("air_quality.breakpoints.{$gas}");

        if (empty($breakpoints)) {
            throw new \InvalidArgumentException("Breakpoint untuk gas '{$gas}' tidak ditemukan.");
        }

        $ppm = max(0.0, $ppm);
        $maxIndex = (float) config('air_quality.max_index', 500);

        for ($i = 1; $i < count($breakpoints); $i++) {
            [$cLow, $iLow] = $breakpoints[$i - 1];
            [$cHigh, $iHigh] = $breakpoints[$i];

            if ($ppm <= $cHigh) {
                if ($cHigh == $cLow) {
                    return (float) $iHigh;
                }

                return $iLow + ($iHigh - $iLow) * ($ppm - $cLow) / ($cHigh - $cLow);
            }
        }

        return $maxIndex;
    }

    /**
     * Hitung Kualitas Udara (KU) lengkap: nilai KU = sub-indeks tertinggi,
     * status kategori, parameter dominan, dan seluruh sub-indeks.
     *
     * @return array{value: float, status: string, dominant: string, sub_indices: array<string, float>}
     */
    public static function computeKu(float $co2, float $nh3, float $co, float $h2): array
    {
        $concentrations = compact('co2', 'nh3', 'co', 'h2');

        $subIndices = [];
        foreach (self::GASES as $gas) {
            $subIndices[$gas] = round(self::computeSubIndex($gas, $concentrations[$gas]), 1);
        }

        // Gas dengan sub-indeks tertinggi menjadi parameter dominan
        $dominant = self::GASES[0];
        foreach ($subIndices as $gas => $index) {
            if ($index > $subIndices[$dominant]) {
                $dominant = $gas;
            }
        }

        $value = $subIndices[$dominant];

        return [
            'value' => $value,
            'status' => self::computeKuStatus($value),
            'dominant' => $dominant,
            'sub_indices' => $subIndices,
        ];
    }

    /**
     * Hitung nilai KU (indeks 0–500) sebagai sub-indeks tertinggi dari 4 gas.
     * Dipertahankan untuk kompatibilitas; gunakan computeKu() untuk detail.
     */
    public static function computeKuValue(float $co2, float $nh3, float $co, float $h2): float
    {
        return self::computeKu($co2, $nh3, $co, $h2)['value'];
    }

    /**
     * Tentukan kategori status berdasarkan nilai indeks KU
     */
    public static function computeKuStatus(float $kuValue): string
    {
        $categories = config('air_quality.categories', [
            'Baik' => 50,
            'Sedang' => 150,
            'Tidak Sehat' => 300,
        ]);

        foreach ($categories as $status => $upperLimit) {
            if ($kuValue <= $upperLimit) {
                return $status;
            }
        }

        return config('air_quality.fallback_category', 'Berbahaya');
    }
}
