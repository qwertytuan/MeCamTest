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
        Schema::create('thumbnail_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->constrained('cameras')->onDelete('cascade');

            // Thumbnail Info
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->integer('file_size')->unsigned()->default(0); // bytes

            // Image Properties
            $table->integer('width')->default(320);
            $table->integer('height')->default(240);

            // Detection Info
            $table->boolean('is_detection_thumbnail')->default(false);
            $table->enum('detection_type', ['NONE', 'MOTION', 'HUMAN'])->default('NONE');
            $table->foreignId('recording_id')->nullable()->constrained('video_recordings')->onDelete('set null');

            $table->timestamp('captured_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->index('camera_id');
            $table->index('captured_at');
            $table->index('is_detection_thumbnail');
            $table->index('recording_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thumbnail_images');
    }
};

