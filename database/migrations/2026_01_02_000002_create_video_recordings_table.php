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
        Schema::create('video_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained('cameras')->onDelete('cascade');

            // Recording Info
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->bigInteger('file_size')->unsigned()->default(0); // bytes
            $table->integer('duration')->default(0); // seconds

            // Detection Info
            $table->enum('detection_type', ['MOTION', 'HUMAN', 'MOTION_HUMAN', 'MANUAL']);
            $table->timestamp('triggered_at');

            // Metadata
            $table->string('resolution', 20)->nullable();
            $table->integer('fps')->nullable();
            $table->string('codec', 50)->default('H264');

            // Status
            $table->enum('status', ['RECORDING', 'COMPLETED', 'FAILED', 'DELETED'])->default('RECORDING');
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('camera_id');
            $table->index('detection_type');
            $table->index('triggered_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_recordings');
    }
};

