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
        Schema::create('company_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('taskit_users')->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('taskit_users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('taskit_companies')->onDelete('cascade');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['recipient_id', 'is_read']);
            $table->index(['sender_id', 'recipient_id']);
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_messages');
    }
};