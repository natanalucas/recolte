<?php
// database/migrations/YYYY_MM_DD_create_certification_paletisation_lot_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certification_paletisation_lot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paletisation_lot_id')->constrained('paletisation_lots')->onDelete('cascade');
            $table->foreignId('type_certification_id')->constrained('type_certifications')->onDelete('cascade');
            $table->timestamps();
            // Nouveau nom d'index raccourci
            $table->unique(['paletisation_lot_id', 'type_certification_id'], 'paletisation_lot_cert_unique');
        });

        Schema::table('paletisations', function (Blueprint $table) {
            if (Schema::hasColumn('paletisations', 'type_certification_id')) {
                $table->dropForeign(['type_certification_id']);
                $table->dropColumn('type_certification_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certification_paletisation_lot');

        Schema::table('paletisations', function (Blueprint $table) {
            $table->foreignId('type_certification_id')->nullable()->constrained('type_certifications')->nullOnDelete();
        });
    }
};