<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Restaurant;
use App\Models\RestaurantUser;
use App\Models\RestaurantCustomer;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Users
        $admin = User::firstOrCreate(['email' => 'admin@tiffin.com'], [
            'name' => 'Super Admin',
            'phone' => '+447000000001',
            'password' => Hash::make('password123'),
            'firebase_uid' => 'mock-uid-admin',
            'role' => 'SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        $restaurantManager = User::firstOrCreate(['email' => 'restaurant@tiffin.com'], [
            'name' => 'Restaurant Manager',
            'phone' => '+447000000002',
            'password' => Hash::make('password123'),
            'firebase_uid' => 'mock-uid-restaurant',
            'role' => 'RESTAURANT',
            'status' => 'ACTIVE',
        ]);

        $customer = User::firstOrCreate(['email' => 'customer@tiffin.com'], [
            'name' => 'John Customer',
            'phone' => '+447000000003',
            'password' => Hash::make('password123'),
            'firebase_uid' => 'mock-uid-customer',
            'role' => 'CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        $postmanCustomer = User::firstOrCreate(['email' => 'hinal@example.com'], [
            'name' => 'Hinal Shah',
            'phone' => '9876543210',
            'password' => Hash::make('password123'),
            'role' => 'CUSTOMER',
            'status' => 'ACTIVE',
        ]);

        $postmanRestaurant = User::firstOrCreate(['email' => 'restaurant@example.com'], [
            'name' => 'Ghar Ka Khana Manager',
            'phone' => '9876543211',
            'password' => Hash::make('password123'),
            'role' => 'RESTAURANT',
            'status' => 'ACTIVE',
        ]);

        // 2. Create Restaurant
        $baseUrl = rtrim(config('app.url', 'http://192.168.1.231:8000'), '/');

        $restaurant = Restaurant::firstOrCreate(['email' => 'royal@tiffin.com'], [
            'name' => 'Royal Tiffin Service',
            'phone' => '+447111222333',
            'logo' => $baseUrl . '/storage/restaurants/royal_tiffin.png',
            'description' => 'Gourmet daily lunch and dinner tiffin deliveries.',
            'address' => '100 Food Plaza, High Street',
            'city' => 'London',
            'state' => 'Greater London',
            'country' => 'United Kingdom',
            'pincode' => 'EC1A 1BB',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'opening_time' => '08:00:00',
            'closing_time' => '22:00:00',
            'status' => 'ACTIVE',
        ]);

        // Link Restaurant User
        RestaurantUser::firstOrCreate([
            'restaurant_id' => $restaurant->id,
            'user_id' => $restaurantManager->id,
        ], [
            'role' => 'owner',
            'status' => 'ACTIVE',
        ]);

        RestaurantUser::firstOrCreate([
            'restaurant_id' => $restaurant->id,
            'user_id' => $postmanRestaurant->id,
        ], [
            'role' => 'owner',
            'status' => 'ACTIVE',
        ]);

        // Link Restaurant Customer
        RestaurantCustomer::firstOrCreate([
            'restaurant_id' => $restaurant->id,
            'customer_id' => $customer->id,
        ], [
            'status' => 'ACTIVE',
        ]);

        // 3. Create Categories
        $gujarati = MenuCategory::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Gujarati Thali',
            'description' => 'Authentic Gujarati daily meal boxes.',
            'image' => $baseUrl . '/storage/menu-categories/gujarati.png',
            'sort_order' => 1,
            'status' => 'ACTIVE',
        ]);

        $punjabi = MenuCategory::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Punjabi Thali',
            'description' => 'Rich and savory North Indian meals.',
            'image' => $baseUrl . '/storage/menu-categories/punjabi.png',
            'sort_order' => 2,
            'status' => 'ACTIVE',
        ]);

        // 4. Create Menu Items
        $dhokla = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $gujarati->id,
            'name' => 'Khaman Dhokla',
            'description' => 'Steamed savory gram flour cakes.',
            'image' => $baseUrl . '/storage/menu-items/dhokla.png',
            'price' => 4.99,
            'discount_price' => 3.99,
            'veg_type' => 'VEG',
            'availability' => true,
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        $gujThali = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $gujarati->id,
            'name' => 'Premium Gujarati Thali',
            'description' => 'Includes 2 Shaak, 4 Rotli, Dal, Rice, Sweet and Farsan.',
            'image' => $baseUrl . '/storage/menu-items/gujthali.png',
            'price' => 12.50,
            'discount_price' => 10.99,
            'veg_type' => 'VEG',
            'availability' => true,
            'status' => 'ACTIVE',
            'sort_order' => 2,
        ]);

        $paneerTikka = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $punjabi->id,
            'name' => 'Paneer Butter Masala Thali',
            'description' => 'Rich cottage cheese curry with 3 butter rotis, pulao and salad.',
            'image' => $baseUrl . '/storage/menu-items/paneer.png',
            'price' => 13.99,
            'discount_price' => 12.50,
            'veg_type' => 'VEG',
            'availability' => true,
            'status' => 'ACTIVE',
            'sort_order' => 1,
        ]);

        // 5. Create Subscription Plans
        $monthlyLunch = SubscriptionPlan::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Monthly Lunch Plan',
            'description' => 'Enjoy 30 days of Gujarati lunch boxes delivered to your door.',
            'price' => 250.00,
            'duration_value' => 1,
            'duration_type' => 'MONTH',
            'meal_type' => 'lunch',
            'meals_per_day' => 1,
            'total_meals' => 30,
            'delivery_frequency' => 'daily',
            'start_date' => now()->toDateString(),
            'status' => 'ACTIVE',
        ]);

        // 6. Create Customer Address
        $address = Address::create([
            'customer_id' => $customer->id,
            'name' => 'John Home',
            'phone' => '+447000000003',
            'address_line_1' => 'Flat 4B, Baker Street',
            'address_line_2' => 'Marylebone',
            'city' => 'London',
            'state' => 'Greater London',
            'country' => 'United Kingdom',
            'pincode' => 'NW1 6XE',
            'latitude' => 51.5237,
            'longitude' => -0.1585,
            'is_default' => true,
        ]);

        // 7. Create Active Subscription
        $subscription = Subscription::create([
            'restaurant_id' => $restaurant->id,
            'customer_id' => $customer->id,
            'subscription_plan_id' => $monthlyLunch->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'price' => 250.00,
            'payment_status' => 'PAID',
            'status' => 'ACTIVE',
            'auto_renew' => true,
        ]);
    }
}
