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
        Schema::create('producteurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('phone', 20)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('ggn', 50)->unique()->nullable();
            $table->string('produit', 100);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('parcelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producteur_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('num');
            $table->unsignedInteger('pieds')->default(0);
            $table->decimal('surface', 8, 4)->comment('Hectares');
            $table->decimal('gps_lat',  10, 7)->nullable();
            $table->decimal('gps_long', 10, 7)->nullable();
            $table->string('localisation', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('dates_controle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producteur_id')->constrained()->cascadeOnDelete();
            $table->date('date_controle');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controles');
        Schema::dropIfExists('parcelles');
        Schema::dropIfExists('producteurs');
    }
};
