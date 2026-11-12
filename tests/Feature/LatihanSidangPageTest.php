<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatihanSidangPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_hidden_path_shows_the_practice_page_without_indexing(): void
    {
        $response = $this->get('/'.config('latihan_sidang.path'));

        $response->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Latihan Sidang SIMKU')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_guessable_path_is_not_found(): void
    {
        $this->get('/latihan-sidang')->assertNotFound();
    }

    public function test_public_menu_does_not_link_to_the_practice_page(): void
    {
        $this->get('/panduan')->assertOk()->assertDontSee(config('latihan_sidang.path'));
    }
}
