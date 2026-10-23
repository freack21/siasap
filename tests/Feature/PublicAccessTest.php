<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_login_routes_are_gone(): void
    {
        foreach (['/login', '/admin', '/admin/pengujian-jaringan', '/admin/kuesioner'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_public_pages_have_no_admin_link(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Masuk Admin')
            ->assertDontSee('Panel Admin');
    }
}
