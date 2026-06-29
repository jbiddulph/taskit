<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_inspection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('taskit_inspections')->cascadeOnDelete();
            $table->string('item_key')->nullable();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_inspection_photos');
    }
};
