<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taskit_operational_object_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operational_object_id')
                ->constrained('taskit_operational_objects')
                ->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')
                ->nullable()
                ->constrained('taskit_users')
                ->nullOnDelete();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size');
            $table->string('caption')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_cover')->default(false);
            $table->timestamps();

            $table->index(['operational_object_id', 'sort_order']);
            $table->index(['operational_object_id', 'is_cover']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taskit_operational_object_photos');
    }
};
