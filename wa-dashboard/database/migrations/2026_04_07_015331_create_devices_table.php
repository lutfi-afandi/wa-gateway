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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Contoh: "WA Kantor", "WA Pribadi"
            $table->string('number')->nullable(); // Terisi otomatis setelah login
            $table->enum('status', ['connected', 'disconnected', 'pairing'])->default('disconnected');
            $table->text('session_path')->nullable(); // Folder session Node.js
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
