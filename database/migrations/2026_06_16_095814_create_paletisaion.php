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
        Schema::create('paletisations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enqueteur_id')->nullable()->constrained('enqueteurs')->nullOnDelete();
            $table->string('fiche_number')->nullable();
            $table->string('num_palette')->nullable();
            // Poids du carton : '2.5' ou '5' (kg)
            $table->string('type_carton')->nullable();
            $table->foreignId('type_certification_id')->nullable()
                ->constrained('type_certifications')->nullOnDelete();
            $table->dateTime('debut')->nullable();
            $table->dateTime('fin')->nullable();
            $table->timestamps();
 
            $table->index('fiche_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paletisations');
    }
};
