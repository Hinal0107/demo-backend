<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add selected_restaurant_id to users table
        if (!Schema::hasColumn('users', 'selected_restaurant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('selected_restaurant_id')->nullable()->after('status')->constrained('restaurants')->onDelete('set null');
            });
        }

        // 2. Add delivery_radius_km to restaurants table
        if (!Schema::hasColumn('restaurants', 'delivery_radius_km')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->decimal('delivery_radius_km', 8, 2)->default(10.00)->after('longitude');
            });
        }

        // 3. Add daily_meal_id to cart_items table
        if (!Schema::hasColumn('cart_items', 'daily_meal_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('daily_meal_id')->nullable()->after('addon_id')->constrained('daily_meals')->onDelete('cascade');
            });
        }

        // 4. Add daily_meal_id to order_items table
        if (!Schema::hasColumn('order_items', 'daily_meal_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreignId('daily_meal_id')->nullable()->after('addon_id')->constrained('daily_meals')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('order_items', 'daily_meal_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropForeign(['daily_meal_id']);
                $table->dropColumn('daily_meal_id');
            });
        }

        if (Schema::hasColumn('cart_items', 'daily_meal_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->dropForeign(['daily_meal_id']);
                $table->dropColumn('daily_meal_id');
            });
        }

        if (Schema::hasColumn('restaurants', 'delivery_radius_km')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropColumn('delivery_radius_km');
            });
        }

        if (Schema::hasColumn('users', 'selected_restaurant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['selected_restaurant_id']);
                $table->dropColumn('selected_restaurant_id');
            });
        }
    }
};
