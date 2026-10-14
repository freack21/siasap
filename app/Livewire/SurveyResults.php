<?php

namespace App\Livewire;

use App\Models\SurveyResponse;
use App\Support\Sus;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts::admin')]
#[Title('Hasil Kuesioner — SIASAP')]
class SurveyResults extends Component
{
    use WithPagination;

    #[Url(as: 'kelompok', except: '')]
    public string $group = '';

    public function mount(): void
    {
        $this->authorizeView();
    }

    public function updatedGroup(): void
    {
        if ($this->group !== '' && ! array_key_exists($this->group, Sus::GROUPS)) {
            $this->group = '';
        }

        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $this->authorizeView();

        SurveyResponse::findOrFail($id)->delete();

        session()->flash('message', 'Satu respons kuesioner telah dihapus.');
    }

    public function export(): StreamedResponse
    {
        $this->authorizeView();

        $filename = 'hasil-sus-siasap-'.now()->format('Ymd-His').'.csv';
        $query = $this->query()->oldest('id');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar terbaca rapi di Excel

            $header = ['no', 'waktu', 'nama', 'kelompok'];
            foreach (array_keys(Sus::ITEMS) as $i) {
                $header[] = "q{$i}";
            }
            array_push($header, 'skor_sus', 'grade', 'adjective', 'acceptability', 'komentar');
            fputcsv($out, $header);

            $no = 0;
            $query->chunk(200, function ($responses) use ($out, &$no) {
                foreach ($responses as $response) {
                    $grade = $response->grade();
                    $row = [
                        ++$no,
                        $response->created_at?->format('Y-m-d H:i:s'),
                        $response->respondent_name ?? 'Anonim',
                        $response->groupLabel(),
                        ...array_values($response->answers()),
                        number_format($response->sus_score, 2, '.', ''),
                        $grade['grade'],
                        $grade['adjective'],
                        Sus::acceptability($response->sus_score)['label'],
                        $response->comment,
                    ];
                    fputcsv($out, $row);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function authorizeView(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->hasPermission('survey.view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat hasil kuesioner.');
        }
    }

    protected function query(): Builder
    {
        return SurveyResponse::query()
            ->when($this->group !== '', fn (Builder $q) => $q->where('respondent_group', $this->group));
    }

    /**
     * @return array<string, mixed>
     */
    protected function summary(): array
    {
        $select = [];
        foreach (array_keys(Sus::ITEMS) as $i) {
            $select[] = "AVG(q{$i}) as q{$i}";
        }
        $means = $this->query()->toBase()->selectRaw(implode(', ', $select))->first();

        $scores = $this->query()->pluck('sus_score')->map(fn ($s) => (float) $s)->all();
        $count = count($scores);
        $mean = Sus::mean($scores);

        $items = [];
        foreach (Sus::ITEMS as $i => $statement) {
            $itemMean = $count ? (float) $means->{"q{$i}"} : null;
            $contribution = $itemMean !== null ? Sus::contribution($i, $itemMean) * 2.5 : null;
            $items[] = [
                'number' => $i,
                'statement' => $statement,
                'positive' => Sus::isPositive($i),
                'mean' => $itemMean,
                'contribution' => $contribution, // 0–10; jumlah semua butir = rata-rata skor SUS
            ];
        }

        $bands = [];
        foreach (Sus::GRADES as $band) {
            $bands[$band['grade']] = $band + ['count' => 0];
        }
        foreach ($scores as $score) {
            $bands[Sus::grade($score)['grade']]['count']++;
        }

        return [
            'count' => $count,
            'mean' => $mean,
            'sd' => Sus::standardDeviation($scores),
            'grade' => $mean !== null ? Sus::grade($mean) : null,
            'acceptability' => $mean !== null ? Sus::acceptability($mean) : null,
            'items' => $items,
            'bands' => array_values($bands),
        ];
    }

    public function render()
    {
        return view('livewire.survey-results', [
            'summary' => $this->summary(),
            'responses' => $this->query()->latest('id')->paginate(15),
            'groups' => Sus::GROUPS,
            'benchmark' => Sus::AVERAGE_BENCHMARK,
        ]);
    }
}
