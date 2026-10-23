<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Metadata tampilan kualitas udara: kategori dan parameter gas.
 *
 * Satu sumber kebenaran untuk label, warna, deskripsi, dan rekomendasi agar
 * halaman dashboard, riwayat, rekomendasi, dan panduan konsisten.
 */
class AirQuality
{
    /**
     * @return array<string, array{label: string, icon: string, color: string, badge: string, dot: string, soft: string, description: string}>
     */
    public static function categories(): array
    {
        return [
            'Baik' => [
                'label' => 'Baik',
                'icon' => 'tabler-mood-smile',
                'color' => '#16a34a',
                'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                'dot' => 'bg-emerald-500',
                'soft' => 'bg-emerald-50',
                'description' => 'Kualitas udara ruangan nyaman dan tidak berisiko bagi kesehatan.',
            ],
            'Sedang' => [
                'label' => 'Sedang',
                'icon' => 'tabler-mood-empty',
                'color' => '#d97706',
                'badge' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                'dot' => 'bg-amber-500',
                'soft' => 'bg-amber-50',
                'description' => 'Masih dapat diterima, namun mulai ada penumpukan gas di dalam ruangan.',
            ],
            'Tidak Sehat' => [
                'label' => 'Tidak Sehat',
                'icon' => 'tabler-mood-sad',
                'color' => '#ea580c',
                'badge' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
                'dot' => 'bg-orange-500',
                'soft' => 'bg-orange-50',
                'description' => 'Dapat menimbulkan keluhan seperti pusing, sesak, atau iritasi bila terpapar lama.',
            ],
            'Berbahaya' => [
                'label' => 'Berbahaya',
                'icon' => 'tabler-alert-octagon',
                'color' => '#dc2626',
                'badge' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                'dot' => 'bg-rose-500',
                'soft' => 'bg-rose-50',
                'description' => 'Konsentrasi gas tinggi dan berisiko langsung terhadap kesehatan atau keselamatan.',
            ],
        ];
    }

    /**
     * Metadata kategori; status tak dikenal (mis. belum ada data) mendapat gaya netral.
     *
     * @return array{label: string, icon: string, color: string, badge: string, dot: string, soft: string, description: string}
     */
    public static function category(?string $status): array
    {
        return self::categories()[$status] ?? [
            'label' => 'Belum ada data',
            'icon' => 'tabler-clock-pause',
            'color' => '#94a3b8',
            'badge' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            'dot' => 'bg-slate-400',
            'soft' => 'bg-slate-50',
            'description' => 'Belum ada data yang diterima dari sensor.',
        ];
    }

    /**
     * Rentang ppm suatu kategori untuk satu gas, mis. "50 – < 100 ppm".
     * Batas bawah termasuk, batas atas tidak (sesuai klasifikasi).
     */
    public static function thresholdLabel(string $gas, string $category): string
    {
        $limits = config("air_quality.thresholds.$gas");
        $order = config('air_quality.category_order');
        $index = array_search($category, $order, true);

        if (! is_array($limits) || $index === false) {
            throw new InvalidArgumentException("Gas atau kategori tidak dikenal: $gas / $category");
        }

        return match (true) {
            $index === 0 => '0 – < '.$limits[0].' ppm',
            $index === count($limits) => '≥ '.$limits[$index - 1].' ppm',
            default => $limits[$index - 1].' – < '.$limits[$index].' ppm',
        };
    }

    /**
     * @return array<string, array{key: string, icon: string, symbol: string, name: string, sensor: string, color: string, description: string, source: string}>
     */
    public static function gases(): array
    {
        return [
            'co2' => [
                'key' => 'co2',
                'icon' => 'tabler-cloud',
                'symbol' => 'CO₂',
                'name' => 'Karbon Dioksida',
                'sensor' => 'MQ-135',
                'color' => '#0ea5e9',
                'description' => 'Hasil pernapasan manusia. Kadar tinggi menandakan ventilasi ruangan kurang.',
                'source' => 'Pernapasan, ruangan tertutup dan padat',
            ],
            'nh3' => [
                'key' => 'nh3',
                'icon' => 'tabler-flask-2',
                'symbol' => 'NH₃',
                'name' => 'Amonia',
                'sensor' => 'MQ-135',
                'color' => '#8b5cf6',
                'description' => 'Gas berbau tajam yang mengiritasi mata dan saluran napas.',
                'source' => 'Pembersih rumah tangga, sampah organik, kotoran hewan',
            ],
            'co' => [
                'key' => 'co',
                'icon' => 'tabler-flame',
                'symbol' => 'CO',
                'name' => 'Karbon Monoksida',
                'sensor' => 'MQ-2',
                'color' => '#f43f5e',
                'description' => 'Gas tidak berwarna dan tidak berbau yang mengikat oksigen dalam darah.',
                'source' => 'Pembakaran tidak sempurna: kompor, asap rokok, kendaraan',
            ],
            'h2' => [
                'key' => 'h2',
                'icon' => 'tabler-atom',
                'symbol' => 'H₂',
                'name' => 'Hidrogen',
                'sensor' => 'MQ-2',
                'color' => '#f59e0b',
                'description' => 'Gas mudah terbakar; dipantau sebagai indikator risiko kebakaran.',
                'source' => 'Pengisian aki/baterai, kebocoran gas industri',
            ],
        ];
    }

    /**
     * Status perangkat dianggap daring bila data terakhir masuk dalam rentang ini.
     */
    public static function onlineThresholdSeconds(): int
    {
        return (int) config('air_quality.online_threshold_seconds', 30);
    }
}
