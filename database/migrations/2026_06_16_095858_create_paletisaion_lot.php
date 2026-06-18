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
        Schema::create('paletisation_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paletisation_id')->constrained('paletisations')->cascadeOnDelete();
            // Numéro du lot dans la palette : 1, 2 ou 3
            $table->unsignedTinyInteger('lot_number');
            $table->foreignId('code_traca_id')->nullable()->constrained('code_traca')->nullOnDelete();
            $table->unsignedInteger('nb_cartons')->nullable();
            $table->timestamps();
 
            $table->unique(['paletisation_id', 'lot_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paletisation_lots');
    }
};
