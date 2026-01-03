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
        Schema::create('cameras', function (Blueprint $table) {
            $table->id();
            $table->string('name',100)->unique();
            $table->string('description')->nullable();
            $table->string('location', 100);
            $table->enum('connection_type', ['USB', 'STREAM']);
            $table->string('usb_path')->nullable();
            $table->string('stream_url',500)->nullable();
            $table->string('stream_username',255)->nullable();
            $table->string('stream_password',255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('resolution')->default('640x640');
            $table->integer('frame_rate')->default(30);
            $table->index('connection_type');
            $table->index('is_active');
            $table->unsignedBigInteger('added_by')->nullable();
            $table->foreign('added_by')->references('id')->on('users')->onDelete('SET NULL');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cameras');
    }
};
