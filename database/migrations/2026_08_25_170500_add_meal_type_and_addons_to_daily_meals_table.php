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
        Schema::table('daily_meals', function (Blueprint $table) {
            $table->string('meal_type')->default('TODAY')->after('veg_type');
            $table->json('addons')->nullable()->after('meal_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_meals', function (Blueprint $table) {
            $table->dropColumn(['meal_type', 'addons']);
        });
    }
};
