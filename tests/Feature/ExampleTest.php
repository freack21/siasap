<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SetupRbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $this->seed(SetupRbacSeeder::class);

        $user = User::where('email', 'admin@siasap.test')->first();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }
}
