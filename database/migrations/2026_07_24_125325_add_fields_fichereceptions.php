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
        Schema::table('fiche_receptions', function (Blueprint $table) {
            $table->foreignId('parcelle_id')->nullable()->constrained('parcelles')->nullOnDelete();
            $table->string('voiture')->nullable();
            $table->string('commune')->nullable();
            $table->integer('caissette')->nullable();       // Nombre de caissettes livrées
            // pas de colonne quantite_kg → calculé = caissette × poids_par_caissette
            $table->timestamp('collecte')->nullable();
            $table->timestamp('depart_champ')->nullable();
            $table->timestamp('retour_station')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
