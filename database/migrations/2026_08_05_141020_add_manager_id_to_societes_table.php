<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('societes', function (Blueprint $table) {
            // Ajout de la clé étrangère (nullable ou non, selon vos règles métier)
            $table->foreignId('manager_id')->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('societes', function (Blueprint $table) {
            //
        });
    }
};
