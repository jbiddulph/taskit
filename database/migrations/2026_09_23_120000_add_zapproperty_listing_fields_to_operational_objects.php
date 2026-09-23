<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taskit_operational_objects', function (Blueprint $table) {
            $table->boolean('show_on_zapproperty')->default(false)->after('occupancy_status');
            $table->string('listing_type', 20)->nullable()->after('show_on_zapproperty');
            $table->decimal('price_amount', 12, 2)->nullable()->after('listing_type');
            $table->string('price_qualifier', 30)->nullable()->after('price_amount');
            $table->unsignedTinyInteger('bathrooms')->nullable()->after('price_qualifier');
            $table->unsignedTinyInteger('receptions')->nullable()->after('bathrooms');
            $table->string('furnishing', 30)->nullable()->after('receptions');
            $table->decimal('deposit_amount', 12, 2)->nullable()->after('furnishing');
            $table->date('available_from')->nullable()->after('deposit_amount');
            $table->string('council_tax_band', 20)->nullable()->after('available_from');
            $table->string('epc_rating', 8)->nullable()->after('council_tax_band');
            $table->string('broadband', 120)->nullable()->after('epc_rating');
            $table->json('key_features')->nullable()->after('broadband');
            $table->text('listing_description')->nullable()->after('key_features');
            $table->json('listing_visibility')->nullable()->after('listing_description');

            $table->index(['show_on_zapproperty', 'listing_type']);
        });
    }

    public function down(): void
    {
        Schema::table('taskit_operational_objects', function (Blueprint $table) {
            $table->dropIndex(['show_on_zapproperty', 'listing_type']);
            $table->dropColumn([
                'show_on_zapproperty',
                'listing_type',
                'price_amount',
                'price_qualifier',
                'bathrooms',
                'receptions',
                'furnishing',
                'deposit_amount',
                'available_from',
                'council_tax_band',
                'epc_rating',
                'broadband',
                'key_features',
                'listing_description',
                'listing_visibility',
            ]);
        });
    }
};
