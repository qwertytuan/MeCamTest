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
        Schema::table('cameras', function (Blueprint $table) {
            // Alert Email Settings
            $table->string('alert_api_username', 255)->nullable()->after('alert_email');
            $table->string('alert_api_password', 255)->nullable()->after('alert_api_username');
            $table->boolean('alert_enabled')->default(false)->after('alert_api_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn([
                'alert_api_username',
                'alert_api_password',
                'alert_enabled'
            ]);
        });
    }
};
