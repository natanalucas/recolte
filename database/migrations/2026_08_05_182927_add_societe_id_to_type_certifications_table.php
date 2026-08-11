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
        Schema::table('type_certifications', function (Blueprint $table) {
            // On ajoute la clé étrangère (nullable au cas où il y a déjà des données)
            $table->foreignId('societe_id')->nullable()->constrained('societes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('type_certifications', function (Blueprint $table) {
            //
        });
    }
};
