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

    public function test_guest_visiting_admin_is_redirected_to_login()
    {
        $this->get(route('admin.overview'))->assertRedirect(route('login'));
    }

    public function test_home_is_public_for_guests()
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_login_page_renders_for_guests()
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk Panel Pengelola')
            ->assertSee('Kembali ke halaman utama')
            ->assertSee(route('home'), false);
    }

    public function test_can_authenticate_via_livewire_login()
    {
        $this->seed(SetupRbacSeeder::class);

        Livewire::test('auth.login')
            ->set('email', 'admin@siasap.test')
            ->set('password', 'password')
            ->call('authenticate')
            ->assertRedirect(route('admin.overview'));

        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_show_error()
    {
        $this->seed(SetupRbacSeeder::class);

        Livewire::test('auth.login')
            ->set('email', 'admin@siasap.test')
            ->set('password', 'salah')
            ->call('authenticate')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_redirects_to_home()
    {
        $this->seed(SetupRbacSeeder::class);
        $admin = User::where('email', 'admin@siasap.test')->first();

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
