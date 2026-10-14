<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SetupRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_is_accessible_without_login(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $this->seed(SetupRbacSeeder::class);

        $user = User::where('email', 'admin@siasap.test')->first();

        $this->actingAs($user)->get(route('admin.overview'))->assertOk();
    }
}
