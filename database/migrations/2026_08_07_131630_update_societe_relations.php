<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ----------------------------------------------------------
        // 1. Ajouter societe_id à la table users (affectation actuelle)
        // ----------------------------------------------------------
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('societe_id')
                  ->nullable()
                  ->after('role_id')
                  ->constrained('societes')
                  ->nullOnDelete();
        });

        // 2. Remplir users.societe_id avec les données existantes
        //    a) Pour les managers (via societes.manager_id)
        DB::table('users')
            ->join('societes', 'societes.manager_id', '=', 'users.id')
            ->update(['users.societe_id' => DB::raw('societes.id')]);

        //    b) Pour les enquêteurs (via enqueteurs.societe_id)
        //       On évite d'écraser un éventuel manager qui serait aussi enquêteur
        DB::table('users')
            ->join('enqueteurs', 'enqueteurs.user_id', '=', 'users.id')
            ->whereNull('users.societe_id')
            ->update(['users.societe_id' => DB::raw('enqueteurs.societe_id')]);

        // ----------------------------------------------------------
        // 3. Ajouter societe_id à toutes les tables de fiches
        // ----------------------------------------------------------
        $ficheTables = [
            'fiche_receptions',
            'soufrages',
            'triages',
            'paletisations',
            'expeditions'
        ];

        foreach ($ficheTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'societe_id')) {
                    $table->foreignId('societe_id')
                          ->nullable()
                          ->after('id')
                          ->constrained('societes')
                          ->nullOnDelete();
                }
            });
        }

        // 4. Remplir societe_id dans ces fiches depuis les enquêteurs
        //    (on profite du fait que la colonne existe encore dans enqueteurs)
        foreach ($ficheTables as $tableName) {
            DB::table($tableName)
                ->join('enqueteurs', 'enqueteurs.id', '=', $tableName.'.enqueteur_id')
                ->update([$tableName.'.societe_id' => DB::raw('enqueteurs.societe_id')]);
        }

        // ----------------------------------------------------------
        // 5. Supprimer societe_id de la table enqueteurs (redondant)
        // ----------------------------------------------------------
        Schema::table('enqueteurs', function (Blueprint $table) {
            // Laravel résout automatiquement le nom de la clé étrangère
            $table->dropForeign(['societe_id']);
            $table->dropColumn('societe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Remettre societe_id dans enqueteurs (sans restaurer les anciennes données)
        Schema::table('enqueteurs', function (Blueprint $table) {
            $table->foreignId('societe_id')
                  ->nullable()
                  ->constrained('societes')
                  ->nullOnDelete();
        });

        // 2. Supprimer societe_id des tables de fiches
        $ficheTables = [
            'fiche_receptions',
            'soufrages',
            'triages',
            'paletisations',
            'expeditions'
        ];

        foreach ($ficheTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'societe_id')) {
                    $table->dropForeign(['societe_id']);
                    $table->dropColumn('societe_id');
                }
            });
        }

        // 3. Supprimer societe_id de users
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'societe_id')) {
                $table->dropForeign(['societe_id']);
                $table->dropColumn('societe_id');
            }
        });
    }
};