<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename table fcm_tokens to user_devices
        Schema::rename('fcm_tokens', 'user_devices');

        // 2. Modify columns
        Schema::table('user_devices', function (Blueprint $table) {
            $table->renameColumn('token', 'fcm_token');
            $table->renameColumn('last_used_at', 'last_login_at');
            $table->boolean('is_active')->default(true)->after('device_id');
        });

        // 3. Migrate data from 'status' to 'is_active'
        DB::table('user_devices')->where('status', 'INACTIVE')->update(['is_active' => false]);

        // 4. Drop 'status' column
        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_devices', function (Blueprint $table) {
            $table->string('status')->default('ACTIVE')->after('device_id');
        });

        DB::table('user_devices')->where('is_active', false)->update(['status' => 'INACTIVE']);

        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->renameColumn('fcm_token', 'token');
            $table->renameColumn('last_login_at', 'last_used_at');
        });

        Schema::rename('user_devices', 'fcm_tokens');
    }
};
