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
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->nullable()->after('user_id')->constrained('restaurants')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->after('restaurant_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->after('order_id')->constrained('subscriptions')->onDelete('cascade');
            $table->string('notification_idempotency_key')->nullable()->after('message')->index();
            $table->string('status')->default('SENT')->after('notification_idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropForeign(['order_id']);
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn([
                'restaurant_id',
                'order_id',
                'subscription_id',
                'notification_idempotency_key',
                'status',
            ]);
        });
    }
};
