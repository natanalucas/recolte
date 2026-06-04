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
        Schema::create('fiche_receptions', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name')->nullable();
            $table->string('fiche_number')->nullable();
            $table->decimal('poids_par_caissette', 8, 2)->nullable(); // ← global à la fiche
            $table->timestamps();
        });

        Schema::create('reception_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiche_reception_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parcelle_id')->nullable()->constrained('parcelles')->nullOnDelete();
            $table->string('voiture')->nullable();
            $table->string('commune')->nullable();
            $table->integer('caissette')->nullable();       // Nombre de caissettes livrées
            // pas de colonne quantite_kg → calculé = caissette × poids_par_caissette
            $table->timestamp('collecte')->nullable();
            $table->timestamp('depart_champ')->nullable();
            $table->timestamp('retour_station')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_reception');
    }
};
