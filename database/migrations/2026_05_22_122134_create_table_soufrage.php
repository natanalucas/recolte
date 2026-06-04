<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soufrages', function (Blueprint $table) {
            $table->id();

            // Contexte fiche
            $table->string('agent_name')->nullable();
            $table->string('fiche_number')->nullable();
            $table->foreignId('raqt_id')->nullable()->constrained('raqt')->nullOnDelete();
            $table->string('lieu_traitement')->nullable();

            // Données ligne
            $table->string('cycle')->nullable();
            $table->string('box')->nullable();
            $table->string('concent')->nullable();
            $table->string('parcelle')->nullable();
            //$table->string('code')->nullable();
            $table->integer('caissette')->nullable();
            $table->decimal('soufre', 10, 2)->nullable();
            $table->dateTime('debut')->nullable();
            $table->dateTime('fin')->nullable();
            $table->foreignId('operateur_id')->nullable()->constrained('operateurs')->nullOnDelete();
            $table->boolean('controle_raqt')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soufrages');
    }
};