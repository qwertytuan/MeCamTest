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
            // Detection Settings
            $table->enum('detection_type', ['NONE', 'MOTION', 'HUMAN', 'MOTION_HUMAN'])->default('NONE')->after('frame_rate');
            $table->boolean('detection_enabled')->default(false)->after('detection_type');
            $table->integer('detection_sensitivity')->default(50)->after('detection_enabled'); // 1-100 scale

            // Recording Settings
            $table->integer('recording_duration')->default(180)->after('detection_sensitivity'); // seconds (3 minutes)

            // Thumbnail Settings
            $table->boolean('thumbnail_enabled')->default(true)->after('recording_duration');
            $table->string('thumbnail_url', 500)->nullable()->after('thumbnail_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn([
                'detection_type',
                'detection_enabled',
                'detection_sensitivity',
                'recording_duration',
                'thumbnail_enabled',
                'thumbnail_url'
            ]);
        });
    }
};

