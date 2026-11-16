<?php

namespace Database\Seeders;

use App\Models\UeqResponse;
use App\Support\Ueq;
use Illuminate\Database\Seeder;

class UeqResponseSeeder extends Seeder
{
    /** Jumlah responden yang mau di-seed. */
    private const JUMLAH_RESPONDEN = 40;

    public function run(): void
    {
        $items    = array_keys(config('ueq.items'));
        $reversed = config('ueq.reversed', []);

        for ($i = 1; $i <= self::JUMLAH_RESPONDEN; $i++) {
            $answers = [];
            foreach ($items as $item) {
                $answers[$item] = $this->skorPositif(
                    in_array($item, $reversed, true)
                );
            }

            $waktu = now()
                ->subDays(mt_rand(1, 30))
                ->subMinutes(mt_rand(0, 1440));

            $response = new UeqResponse();
            $response->timestamps = false; // matikan auto-timestamp
            $response->forceFill([
                'respondent_name' => null, // selalu anonim
                'answers'         => $answers,
                'ip_hash'         => null,
                'created_at'      => $waktu,
                'updated_at'      => $waktu,
            ]);
            $response->save();
        }

        $this->command?->info(
            '✅ '.self::JUMLAH_RESPONDEN.' respons UEQ berhasil di-seed '
            .'(anonim, condong positif).'
        );
    }

    private function skorPositif(bool $reversed): int
    {
        $roll = mt_rand(1, 100);

        $nilai = match (true) {
            $roll <= 62 => mt_rand(6, 7),
            $roll <= 82 => 5,
            $roll <= 92 => 4,
            $roll <= 97 => 3,
            default     => mt_rand(1, 2),
        };

        return $reversed
            ? (Ueq::MIN + Ueq::MAX - $nilai) // cermin 1↔7
            : $nilai;
    }
}
