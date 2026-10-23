<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\SetupRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SetupRbacSeeder::class);
    }

    public function test_public_pages_use_topbar_without_admin_sidebar(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Navigasi utama')
            ->assertSee(route('history'))
            ->assertSee(route('guide'))
            ->assertSee(route('survey'))
            ->assertSee('Masuk Admin')
            ->assertDontSee('Navigasi admin');
    }

    public function test_admin_sees_all_sidebar_links(): void
    {
        $admin = User::where('email', 'admin@siasap.test')->first();

        $this->actingAs($admin)
            ->get(route('admin.overview'))
            ->assertOk()
            ->assertSee('Navigasi admin')
            ->assertSee(route('admin.data'))
            ->assertSee(route('admin.rooms'))
            ->assertSee(route('admin.qos'));
    }

    public function test_sidebar_hides_links_without_permission(): void
    {
        $researcher = User::create([
            'name' => 'Peneliti',
            'email' => 'peneliti@siasap.test',
            'password' => 'password',
        ]);
        $researcher->roles()->sync([Role::where('name', 'researcher')->value('id')]);

        $this->actingAs($researcher)
            ->get(route('admin.overview'))
            ->assertOk()
            ->assertSee(route('admin.qos'));
    }

    public function test_admin_pages_require_login(): void
    {
        $this->get(route('admin.overview'))->assertRedirect(route('login'));
    }

    public function test_removed_routes_return_404(): void
    {
        $admin = User::where('email', 'admin@siasap.test')->first();

        foreach (['/admin/telemetry', '/admin/qos-analytics', '/admin/rooms', '/admin/sessions-management', '/admin/rbac-management', '/admin/navigation-management'] as $uri) {
            $this->actingAs($admin)->get($uri)->assertNotFound();
        }
    }
}
