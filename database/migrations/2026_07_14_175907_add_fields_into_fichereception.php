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
        Schema::table('reception_lignes', function (Blueprint $table) {
            $table->decimal('pourcentage_dechet', 5, 2)
                ->nullable()
                ->after('caissette');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reception_lignes', function (Blueprint $table) {
            $table->dropColumn('pourcentage_dechet');
        });
    }
};
