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
        // database/migrations/xxxx_xx_xx_create_expeditions_table.php
        Schema::create('expeditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enqueteur_id')->nullable()->constrained('enqueteurs')->nullOnDelete();
            $table->string('fiche_number')->nullable();
            $table->string('conteneur')->nullable();
            $table->string('immatriculation')->nullable();
            $table->string('proprete_conteneur')->default('propre');
            $table->string('proprete_camion')->default('propre');
            $table->dateTime('debut_empotage')->nullable();
            $table->dateTime('fin_empotage')->nullable();
            $table->dateTime('depart_station')->nullable();
            $table->dateTime('arrivee_port')->nullable();
            $table->string('bateau')->nullable();
            $table->string('bon_livraison')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        // database/migrations/xxxx_xx_xx_create_expedition_palettes_table.php
        Schema::create('expedition_palettes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expedition_id')->constrained()->onDelete('cascade');
            $table->foreignId('paletisation_id')->constrained('paletisations'); // Lien vers le modèle existant
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedition');
    }
};
