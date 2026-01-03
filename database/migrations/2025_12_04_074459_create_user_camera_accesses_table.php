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
        Schema::create('user_camera_accesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('camera_id');
            $table->timestamp('access_starts_at')->useCurrent();
            $table->dateTime('access_expires_at')->nullable();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_control')->default(false);
            $table->boolean('can_configure')->default(false);
            $table->unsignedBigInteger('granted_by_admin_id')->nullable();
            $table->timestamps();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('camera_id')
                ->references('id')
                ->on('cameras')
                ->onDelete('cascade');

            $table->foreign('granted_by_admin_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->unique(['user_id', 'camera_id']);
            $table->index('user_id');
            $table->index('camera_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_camera_accesses');
    }
};
