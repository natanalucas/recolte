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
        Schema::table('soufrages', function (Blueprint $table) {
            $table->foreignId('parcelle_id')
                  ->nullable()
                  ->after('parcelle');
        });

        // 3. On applique la contrainte de clé étrangère et on supprime l'ancien champ
        Schema::table('soufrages', function (Blueprint $table) {
            $table->foreign('parcelle_id')
                  ->references('id')
                  ->on('parcelles')
                  ->onDelete('set null'); // Sécurité : si la parcelle est supprimée, la fiche reste mais l'id passe à null

            $table->dropColumn('parcelle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('id_soufrages', function (Blueprint $table) {
            //
        });
    }
};
