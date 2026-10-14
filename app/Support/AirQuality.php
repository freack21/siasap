<?php

namespace App\Support;

/**
 * Metadata tampilan kualitas udara: kategori KU dan parameter gas.
 *
 * Satu sumber kebenaran untuk label, warna, deskripsi, dan rekomendasi agar
 * halaman pengguna, panduan, dan panel admin konsisten.
 */
class AirQuality
{
    /**
     * @return array<string, array{label: string, range: string, color: string, badge: string, dot: string, soft: string, description: string, advice: string}>
     */
    public static function categories(): array
    {
        $limits = config('air_quality.categories', ['Baik' => 50, 'Sedang' => 150, 'Tidak Sehat' => 300]);
        $max = config('air_quality.max_index', 500);

        return [
            'Baik' => [
                'label' => 'Baik',
                'range' => '0 – '.$limits['Baik'],
                'color' => '#16a34a',
                'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                'dot' => 'bg-emerald-500',
                'soft' => 'bg-emerald-50',
                'description' => 'Kualitas udara ruangan nyaman dan tidak berisiko bagi kesehatan.',
                'advice' => 'Pertahankan sirkulasi udara seperti biasa.',
            ],
            'Sedang' => [
                'label' => 'Sedang',
                'range' => ($limits['Baik'] + 1).' – '.$limits['Sedang'],
                'color' => '#d97706',
                'badge' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                'dot' => 'bg-amber-500',
                'soft' => 'bg-amber-50',
                'description' => 'Masih dapat diterima, namun mulai ada penumpukan gas di dalam ruangan.',
                'advice' => 'Buka jendela atau nyalakan ventilasi untuk menambah pertukaran udara.',
            ],
            'Tidak Sehat' => [
                'label' => 'Tidak Sehat',
                'range' => ($limits['Sedang'] + 1).' – '.$limits['Tidak Sehat'],
                'color' => '#ea580c',
                'badge' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
                'dot' => 'bg-orange-500',
                'soft' => 'bg-orange-50',
                'description' => 'Dapat menimbulkan keluhan seperti pusing, sesak, atau iritasi bila terpapar lama.',
                'advice' => 'Kurangi aktivitas di ruangan, perbaiki ventilasi, dan cari sumber gas.',
            ],
            'Berbahaya' => [
                'label' => 'Berbahaya',
                'range' => ($limits['Tidak Sehat'] + 1).' – '.$max,
                'color' => '#dc2626',
                'badge' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                'dot' => 'bg-rose-500',
                'soft' => 'bg-rose-50',
                'description' => 'Konsentrasi gas tinggi dan berisiko langsung terhadap kesehatan atau keselamatan.',
                'advice' => 'Segera tinggalkan ruangan, buka seluruh akses udara, dan periksa sumber kebocoran gas.',
            ],
        ];
    }

    /**
     * Metadata kategori; status tak dikenal (mis. belum ada data) mendapat gaya netral.
     *
     * @return array{label: string, range: string, color: string, badge: string, dot: string, soft: string, description: string, advice: string}
     */
    public static function category(?string $status): array
    {
        return self::categories()[$status] ?? [
            'label' => 'Belum ada data',
            'range' => '-',
            'color' => '#94a3b8',
            'badge' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            'dot' => 'bg-slate-400',
            'soft' => 'bg-slate-50',
            'description' => 'Belum ada data yang diterima dari sensor.',
            'advice' => 'Pastikan perangkat sensor menyala dan terhubung ke jaringan.',
        ];
    }

    /**
     * @return array<string, array{key: string, symbol: string, name: string, sensor: string, color: string, description: string, source: string}>
     */
    public static function gases(): array
    {
        return [
            'co2' => [
                'key' => 'co2',
                'symbol' => 'CO₂',
                'name' => 'Karbon Dioksida',
                'sensor' => 'MQ-135',
                'color' => '#0ea5e9',
                'description' => 'Hasil pernapasan manusia. Kadar tinggi menandakan ventilasi ruangan kurang.',
                'source' => 'Pernapasan, ruangan tertutup dan padat',
            ],
            'nh3' => [
                'key' => 'nh3',
                'symbol' => 'NH₃',
                'name' => 'Amonia',
                'sensor' => 'MQ-135',
                'color' => '#8b5cf6',
                'description' => 'Gas berbau tajam yang mengiritasi mata dan saluran napas.',
                'source' => 'Pembersih rumah tangga, sampah organik, kotoran hewan',
            ],
            'co' => [
                'key' => 'co',
                'symbol' => 'CO',
                'name' => 'Karbon Monoksida',
                'sensor' => 'MQ-2',
                'color' => '#f43f5e',
                'description' => 'Gas tidak berwarna dan tidak berbau yang mengikat oksigen dalam darah.',
                'source' => 'Pembakaran tidak sempurna: kompor, asap rokok, kendaraan',
            ],
            'h2' => [
                'key' => 'h2',
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
