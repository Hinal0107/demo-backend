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
        // 1. Daily Meals table
        Schema::create('daily_meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->date('date');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->enum('veg_type', ['VEG', 'NON_VEG', 'JAIN'])->default('VEG');
            $table->boolean('availability')->default(true);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Addons table
        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('image')->nullable();
            $table->boolean('availability')->default(true);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Taxes table
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Update cart_items table: add addon_id, make menu_item_id nullable
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('addon_id')->nullable()->after('menu_item_id')->constrained('addons')->onDelete('cascade');
            $table->unsignedBigInteger('menu_item_id')->nullable()->change();
        });

        // 5. Update order_items table: add addon_id, make menu_item_id nullable
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('addon_id')->nullable()->after('menu_item_id')->constrained('addons')->onDelete('set null');
            $table->unsignedBigInteger('menu_item_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['addon_id']);
            $table->dropColumn('addon_id');
            $table->unsignedBigInteger('menu_item_id')->nullable(false)->change();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['addon_id']);
            $table->dropColumn('addon_id');
            $table->unsignedBigInteger('menu_item_id')->nullable(false)->change();
        });

        Schema::dropIfExists('taxes');
        Schema::dropIfExists('addons');
        Schema::dropIfExists('daily_meals');
    }
};
