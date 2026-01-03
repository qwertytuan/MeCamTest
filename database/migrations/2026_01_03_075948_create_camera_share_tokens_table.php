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
        Schema::create('camera_share_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('camera_id')->constrained('cameras')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name')->nullable(); // Optional name/description for the share link
            $table->integer('watch_duration')->default(3600); // Duration in seconds (default 1 hour)
            $table->integer('max_views')->nullable(); // Optional max view count
            $table->integer('current_views')->default(0);
            $table->timestamp('expires_at'); // When the token expires
            $table->timestamp('first_accessed_at')->nullable(); // When the stream was first accessed
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for faster lookup
            $table->index(['token', 'is_active']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camera_share_tokens');
    }
};
