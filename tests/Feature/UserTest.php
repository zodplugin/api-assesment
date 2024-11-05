<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'umur' => 10,
            'status_keanggotaan' => 'standard'
        ]);

        $response->assertStatus(201)
                ->assertJson(['message' => 'User registered successfully']); // Adjust the assertion to check for the message

        $this->assertDatabaseHas('users', [
            'email' => 'johndoe@example.com',
        ]);
    }


    public function test_user_can_login()
    {
        User::create([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => Hash::make('password123'),
            'umur' => 10,
            'status_keanggotaan' => 'standard',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'johndoe@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
    }


    public function test_user_can_get_all_users()
    {
        $admin = User::factory()->create();
        $admin->userRoles()->create(['role' => 'admin']);



        $loginResponse = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/users');

        $response->assertStatus(200)
                 ->assertJsonStructure([['id', 'name', 'email']]);
    }

    public function test_user_can_get_single_user()
    {
        $admin = User::factory()->create();
        $admin->userRoles()->create(['role' => 'admin']);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('access_token');
        $user = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $user->id,
                     'name' => $user->name,
                     'email' => $user->email,
                 ]);
    }

    public function test_user_can_update_only_if_admin()
    {
        $admin = User::factory()->create();
        $admin->userRoles()->create(['role' => 'admin']);

        $userToUpdate = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regularuser@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->putJson("/api/users/{$userToUpdate->id}", [
            'name' => 'Updated User',
            'email' => 'updateduser@example.com',
            'umur' => 30,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'name' => 'Updated User',
                    'email' => 'updateduser@example.com',
                    'umur' => 30
                ]);

        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'Updated User',
            'email' => 'updateduser@example.com',
            'umur' => 30
        ]);
    }



    public function test_user_can_delete_only_if_admin()
    {
        $admin = User::factory()->create();
        $admin->userRoles()->create(['role' => 'admin']);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $userToDelete = User::factory()->create();

        $token = $loginResponse->json('access_token');

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->deleteJson("/api/users/{$userToDelete->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'User deleted successfully.']);

        $this->assertDatabaseMissing('users', ['id' => $userToDelete->id]);
    }

    public function test_user_cannot_delete_if_not_admin()
    {
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regularuser@example.com',
            'password' => bcrypt('password123'),
        ]);

        $userToDelete = User::factory()->create([
            'name' => 'User to Delete',
            'email' => 'usertodelete@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'regularuser@example.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->deleteJson("/api/users/{$userToDelete->id}");

        $response->assertStatus(403)
                ->assertJson(['message' => 'Unauthorized. Only admins can perform this action.']);

        $this->assertDatabaseHas('users', ['id' => $userToDelete->id]);
    }




    public function test_unauthorized_user_cannot_access_users()
    {
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);
    }
}
