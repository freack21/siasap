<?php

namespace App\Http\Controllers;

use App\Console\Commands\ExportRoomTestCommand;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman tersembunyi untuk mengunduh hasil telemetry:ekspor-uji-ruangan.
 */
class RoomTestExportController extends Controller
{
    /**
     * Daftar tanggal pengujian yang sudah diekspor beserta berkasnya.
     */
    public function index(): Response
    {
        $disk = Storage::disk('local');

        $exports = collect($disk->directories(ExportRoomTestCommand::FOLDER))
            ->map(fn (string $directory) => basename($directory))
            ->filter(fn (string $date) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1)
            ->sortDesc()
            ->map(fn (string $date) => [
                'date' => $date,
                'files' => collect(ExportRoomTestCommand::FILES)
                    ->filter(fn (string $file) => $disk->exists(ExportRoomTestCommand::FOLDER."/{$date}/{$file}"))
                    ->values()
                    ->all(),
                'summary' => $disk->exists(ExportRoomTestCommand::FOLDER."/{$date}/ringkasan.md")
                    ? $disk->get(ExportRoomTestCommand::FOLDER."/{$date}/ringkasan.md")
                    : null,
            ])
            ->values();

        return response()
            ->view('uji-ruangan.index', ['exports' => $exports])
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function download(string $date, string $file): StreamedResponse
    {
        $path = ExportRoomTestCommand::FOLDER."/{$date}/{$file}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, "uji-ruangan-{$date}-{$file}", [
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
