<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_navigation_lists_six_menus_in_order(): void
    {
        $this->get(route('guide'))
            ->assertOk()
            ->assertSeeInOrder(['Beranda', 'Riwayat', 'Rekomendasi', 'Perangkat', 'Panduan', 'Kuesioner'])
            ->assertSee(route('recommendations'), false)
            ->assertSee(route('devices'), false);
    }
}
