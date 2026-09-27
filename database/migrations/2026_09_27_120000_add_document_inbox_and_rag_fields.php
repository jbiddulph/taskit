<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taskit_operational_documents', function (Blueprint $table) {
            $table->text('extracted_text')->nullable()->after('extracted_data');
            $table->string('match_status', 30)->nullable()->after('extracted_text');
            $table->unsignedTinyInteger('match_confidence')->nullable()->after('match_status');
        });

        // Allow inbox documents (no property yet) and unmatched proposals.
        Schema::table('taskit_operational_documents', function (Blueprint $table) {
            $table->dropForeign(['operational_object_id']);
        });
        Schema::table('taskit_operational_documents', function (Blueprint $table) {
            $table->foreignId('operational_object_id')->nullable()->change();
            $table->foreign('operational_object_id')
                ->references('id')
                ->on('taskit_operational_objects')
                ->nullOnDelete();
        });

        Schema::table('taskit_document_extraction_proposals', function (Blueprint $table) {
            $table->dropForeign(['operational_object_id']);
        });
        Schema::table('taskit_document_extraction_proposals', function (Blueprint $table) {
            $table->foreignId('operational_object_id')->nullable()->change();
            $table->foreign('operational_object_id')
                ->references('id')
                ->on('taskit_operational_objects')
                ->nullOnDelete();
            $table->foreignId('suggested_operational_object_id')
                ->nullable()
                ->after('operational_object_id')
                ->constrained('taskit_operational_objects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('taskit_document_extraction_proposals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suggested_operational_object_id');
            $table->dropForeign(['operational_object_id']);
        });

        // Restore NOT NULL only where possible — leave unmatched rows attached to first site if needed.
        $orphanProposalIds = DB::table('taskit_document_extraction_proposals')
            ->whereNull('operational_object_id')
            ->pluck('id');
        foreach ($orphanProposalIds as $id) {
            $companyId = DB::table('taskit_document_extraction_proposals')->where('id', $id)->value('company_id');
            $siteId = DB::table('taskit_operational_objects')->where('company_id', $companyId)->value('id');
            if ($siteId) {
                DB::table('taskit_document_extraction_proposals')->where('id', $id)->update([
                    'operational_object_id' => $siteId,
                ]);
            }
        }

        Schema::table('taskit_document_extraction_proposals', function (Blueprint $table) {
            $table->foreignId('operational_object_id')->nullable(false)->change();
            $table->foreign('operational_object_id')
                ->references('id')
                ->on('taskit_operational_objects')
                ->cascadeOnDelete();
        });

        Schema::table('taskit_operational_documents', function (Blueprint $table) {
            $table->dropForeign(['operational_object_id']);
        });

        $orphanDocs = DB::table('taskit_operational_documents')->whereNull('operational_object_id')->pluck('id');
        foreach ($orphanDocs as $id) {
            $companyId = DB::table('taskit_operational_documents')->where('id', $id)->value('company_id');
            $siteId = DB::table('taskit_operational_objects')->where('company_id', $companyId)->value('id');
            if ($siteId) {
                DB::table('taskit_operational_documents')->where('id', $id)->update([
                    'operational_object_id' => $siteId,
                ]);
            }
        }

        Schema::table('taskit_operational_documents', function (Blueprint $table) {
            $table->foreignId('operational_object_id')->nullable(false)->change();
            $table->foreign('operational_object_id')
                ->references('id')
                ->on('taskit_operational_objects')
                ->cascadeOnDelete();
            $table->dropColumn(['extracted_text', 'match_status', 'match_confidence']);
        });
    }
};
