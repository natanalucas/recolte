<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certification_triage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('triage_id')->constrained()->onDelete('cascade');
            $table->foreignId('type_certification_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Supprimer l'ancienne colonne type_certification_id de la table triages
        Schema::table('triages', function (Blueprint $table) {
            $table->dropForeign(['type_certification_id']);
            $table->dropColumn('type_certification_id');
        });
    }

    public function down(): void
    {
        Schema::table('triages', function (Blueprint $table) {
            $table->foreignId('type_certification_id')->nullable()->constrained();
        });

        Schema::dropIfExists('certification_triage');
    }
};