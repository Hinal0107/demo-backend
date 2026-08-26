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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('total_meals')->default(7)->after('subscription_plan_id');
            $table->integer('used_meals')->default(0)->after('total_meals');
            $table->integer('remaining_meals')->default(7)->after('used_meals');
            $table->integer('max_validity_days')->default(14)->after('remaining_meals');
            $table->date('max_validity_date')->nullable()->after('max_validity_days');
            $table->string('expiration_reason')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'total_meals',
                'used_meals',
                'remaining_meals',
                'max_validity_days',
                'max_validity_date',
                'expiration_reason',
            ]);
        });
    }
};
