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
// 1. On crée la nouvelle colonne enqueteur_id juste après agent_name (nullable au début)
        Schema::table('fiche_receptions', function (Blueprint $table) {
            $table->foreignId('enqueteur_id')
                  ->nullable()
                  ->after('agent_name');
        });

        // 3. On applique la contrainte de clé étrangère et on supprime l'ancien champ
        Schema::table('fiche_receptions', function (Blueprint $table) {
            $table->foreign('enqueteur_id')
                  ->references('id')
                  ->on('enqueteurs')
                  ->onDelete('set null'); // Sécurité : si l'enquêteur est supprimé, la fiche reste mais l'id passe à null

            $table->dropColumn('agent_name');
        });

        Schema::table('soufrages', function (Blueprint $table) {
            $table->foreignId('enqueteur_id')
                  ->nullable()
                  ->after('agent_name');
        });

        // 3. On applique la contrainte de clé étrangère et on supprime l'ancien champ
        Schema::table('soufrages', function (Blueprint $table) {
            $table->foreign('enqueteur_id')
                  ->references('id')
                  ->on('enqueteurs')
                  ->onDelete('set null'); // Sécurité : si l'enquêteur est supprimé, la fiche reste mais l'id passe à null

            $table->dropColumn('agent_name');
        });

        Schema::table('triages', function (Blueprint $table) {
            $table->foreignId('enqueteur_id')
                  ->nullable()
                  ->after('agent_name');
        });

        // 3. On applique la contrainte de clé étrangère et on supprime l'ancien champ
        Schema::table('triages', function (Blueprint $table) {
            $table->foreign('enqueteur_id')
                  ->references('id')
                  ->on('enqueteurs')
                  ->onDelete('set null'); // Sécurité : si l'enquêteur est supprimé, la fiche reste mais l'id passe à null

            $table->dropColumn('agent_name');
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
