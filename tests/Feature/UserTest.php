<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
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
                ->assertJson(['message' => 'User registered successfully']);
        $this->assertDatabaseHas('users', [
            'email' => 'johndoe@example.com',
        ]);
    }
    public function test_register_fails_when_name_is_missing()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'umur' => 25,
            'status_keanggotaan' => 'premium',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    public function test_register_fails_when_email_is_invalid()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'umur' => 25,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_register_fails_when_password_is_too_short()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
            'umur' => 25,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_register_fails_when_passwords_do_not_match()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'differentpassword',
            'umur' => 25,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_register_fails_when_age_is_missing_or_invalid()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['umur']);

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'umur' => 'invalid',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['umur']);
    }

    public function test_register_fails_when_email_already_exists()
    {
        $existingUser = \App\Models\User::factory()->create([
            'email' => 'existing@example.com'
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'umur' => 30,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }


    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'testuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'expires_in'
                 ]);
    }

    public function test_user_cannot_login_with_invalid_password()
    {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'testuser@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Invalid credentials']);
    }

    public function test_user_cannot_login_with_nonexistent_email()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Invalid credentials']);
    }

    public function test_user_index_with_pagination()
    {
        $admin = User::factory()->create();
        $admin->userRoles()->create(['role' => 'admin']);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('access_token');
        User::factory()->count(25)->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/users?page=1&per_page=10');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'current_page',
                     'data' => [
                         '*' => ['id', 'name', 'email']
                     ],
                     'per_page',
                     'total',
                     'last_page',
                 ])
                 ->assertJsonCount(10, 'data');
    }

    public function test_user_index_caching()
    {
        $admin = User::factory()->create();
        $admin->userRoles()->create(['role' => 'admin']);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('access_token');
        User::factory()->count(5)->create();

        $response1 = $this->getJson('/api/users?page=1&per_page=10');
        $this->assertEquals(6, count($response1->json('data')));

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn($response1->json());

        $response2 = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/users?page=1&per_page=10');
        $this->assertEquals(6, count($response2->json('data')));
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


    public function test_store_creates_user_with_valid_data()
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
        ])->postJson('/api/users', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'umur' => 25,
            'status_keanggotaan' => 'premium',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'name' => 'John Doe',
                     'email' => 'johndoe@example.com',
                     'umur' => 25,
                     'status_keanggotaan' => 'premium',
                 ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'umur' => 25,
            'status_keanggotaan' => 'premium',
        ]);
    }

    public function test_store_fails_with_invalid_email()
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
        ])->postJson('/api/users', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'umur' => 25,
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_store_fails_when_password_too_short()
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
        ])->postJson('/api/users', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'umur' => 25,
            'password' => '123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_store_fails_when_email_already_exists()
    {
        $admin = User::factory()->create();
        $admin->userRoles()->create(['role' => 'admin']);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ]);
        $token = $loginResponse->json('access_token');
        User::factory()->create([
            'email' => 'johndoe@example.com'
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->postJson('/api/users', [
            'name' => 'Jane Doe',
            'email' => 'johndoe@example.com',
            'umur' => 30,
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_store_fails_when_umur_is_missing()
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
        ])->postJson('/api/users', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['umur']);
    }

    public function test_unauthorized_user_cannot_access_users()
    {
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);
    }
}
