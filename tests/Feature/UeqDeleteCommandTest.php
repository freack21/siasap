<?php

namespace Tests\Feature;

use App\Models\UeqResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UeqDeleteCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        UeqResponse::factory()->count(6)->sequence(fn ($sequence) => ['respondent_name' => 'R'.($sequence->index + 1)])->uniform(4)->create();
    }

    public function test_deletes_listed_ids_and_ranges_after_backing_them_up(): void
    {
        $this->artisan('ueq:hapus', ['id' => ['1', '4-5', '99'], '--force' => true])
            ->expectsOutputToContain('Id tidak ditemukan dan dilewati: 99')
            ->expectsOutputToContain('3 jawaban dihapus.')
            ->assertSuccessful();

        $this->assertSame([2, 3, 6], UeqResponse::orderBy('id')->pluck('id')->all());

        $backups = Storage::disk('local')->files('ueq-cadangan');
        $this->assertCount(1, $backups);
        $csv = Storage::disk('local')->get($backups[0]);
        $this->assertStringStartsWith('id,waktu,nama,i1,', $csv);
        $this->assertStringContainsString("\n1,", $csv);
        $this->assertCount(4, array_filter(explode("\n", $csv)));
        $this->assertStringContainsString(',R5,', $csv);
    }

    public function test_nothing_is_deleted_when_not_confirmed(): void
    {
        $this->artisan('ueq:hapus', ['id' => ['2']])
            ->expectsConfirmation('Hapus 1 jawaban UEQ di atas?', 'no')
            ->expectsOutputToContain('Dibatalkan')
            ->assertSuccessful();

        $this->assertSame(6, UeqResponse::count());
        Storage::disk('local')->assertDirectoryEmpty('ueq-cadangan');
    }

    public function test_rejects_malformed_ids(): void
    {
        $this->artisan('ueq:hapus', ['id' => ['abc'], '--force' => true])
            ->expectsOutputToContain('Id tidak valid')
            ->assertFailed();

        $this->assertSame(6, UeqResponse::count());
    }
}
