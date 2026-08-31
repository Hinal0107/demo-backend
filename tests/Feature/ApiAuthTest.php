<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Restaurant;
use App\Models\RestaurantUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sanctum_token_access(): void
    {
        $customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => bcrypt('password123'),
            'role' => 'CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'customer@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        // Test GET /restaurants as customer
        $restaurantsResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/restaurants');
        $restaurantsResponse->assertStatus(200);

        // Test GET /restaurant/profile as customer (should be 403 Forbidden)
        $profileResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/restaurant/profile');
        $profileResponse->assertStatus(403);
    }

    public function test_restaurant_sanctum_token_access(): void
    {
        $user = User::create([
            'name' => 'Test Restaurant Owner',
            'email' => 'restaurant@example.com',
            'password' => bcrypt('password123'),
            'role' => 'RESTAURANT',
            'status' => 'ACTIVE',
        ]);

        $restaurant = Restaurant::create([
            'name' => 'Test Tiffin',
            'email' => 'restaurant@example.com',
            'phone' => '1234567890',
            'address' => '123 Street',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'pincode' => '123456',
            'opening_time' => '09:00:00',
            'closing_time' => '21:00:00',
            'status' => 'ACTIVE',
        ]);

        RestaurantUser::create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'ACTIVE',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'restaurant@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');
        $this->assertNotEmpty($token);

        // Test GET /restaurant/profile as restaurant owner
        $profileResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/restaurant/profile');
        $profileResponse->assertStatus(200);

        // Test GET /restaurant/categories as restaurant owner
        $categoriesResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/restaurant/categories');
        $categoriesResponse->assertStatus(200);
    }

    public function test_firebase_token_access(): void
    {
        $customer = User::create([
            'name' => 'Mock Customer',
            'email' => 'customer@tiffin.com',
            'firebase_uid' => 'mock-uid-customer',
            'role' => 'CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        // Access /restaurants with mock firebase token
        $response = $this->withHeader('Authorization', 'Bearer mock-uid-customer')
            ->getJson('/api/v1/restaurants');

        $response->assertStatus(200);
    }

    public function test_direct_firebase_uid_string_access(): void
    {
        $customer = User::create([
            'name' => 'UID Customer',
            'email' => 'uid_customer@tiffin.com',
            'firebase_uid' => 'IwCnEHePngpvrlvJI8bQaRuv6evmxXMaSPOHP7WW3bd1d564',
            'role' => 'CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        // Access with direct firebase_uid string in Bearer token header
        $response = $this->withHeader('Authorization', 'Bearer IwCnEHePngpvrlvJI8bQaRuv6evmxXMaSPOHP7WW3bd1d564')
            ->getJson('/api/v1/restaurants');

        $response->assertStatus(200);
    }

    public function test_invalid_token_returns_401(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-string')
            ->getJson('/api/v1/restaurants');

        $response->assertStatus(401);
        $response->assertJsonFragment([
            'success' => false,
            'message' => 'Unauthenticated. Please provide a valid authorization token.',
        ]);
    }
}
