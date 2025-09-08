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
        Schema::create('taskit_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Client company name
            $table->string('key_contact_name')->nullable(); // Main contact person
            $table->string('key_contact_email')->nullable(); // Contact email
            $table->string('key_contact_phone')->nullable(); // Contact phone
            $table->string('address_line_1')->nullable(); // Address line 1
            $table->string('address_line_2')->nullable(); // Address line 2 (optional)
            $table->string('city')->nullable(); // City
            $table->string('state_province')->nullable(); // State/Province
            $table->string('postal_code')->nullable(); // Postal/Zip code
            $table->string('country')->nullable(); // Country
            $table->string('website')->nullable(); // Website URL (optional)
            $table->text('notes')->nullable(); // Additional notes
            $table->boolean('is_active')->default(true); // Active status
            $table->foreignId('company_id')->constrained('taskit_companies')->onDelete('cascade'); // Which ZapTask company owns this client
            $table->foreignId('created_by_user_id')->constrained('taskit_users')->onDelete('cascade'); // Who created this client
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['company_id', 'is_active']);
            $table->index('name');
            $table->index('key_contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taskit_clients');
    }
};
