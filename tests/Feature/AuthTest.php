<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SetupRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_can_authenticate_via_livewire_login()
    {
        $this->seed(SetupRbacSeeder::class);

        Livewire::test('auth.login')
            ->set('email', 'admin@siasap.test')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('telemetry'));

        $this->assertAuthenticated();
    }
}
