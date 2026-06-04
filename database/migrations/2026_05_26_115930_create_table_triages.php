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
        // database/migrations/xxxx_create_triages_table.php
        Schema::create('triages', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name')->nullable();
            $table->string('fiche_number')->nullable();
            //$table->string('code')->nullable();
            $table->enum('type_carton', ['2kg', '5.5kg'])->default('5.5kg');
            $table->foreignId('type_certification_id')->nullable()->constrained('type_certifications')->nullOnDelete();
            $table->dateTime('debut')->nullable();
            $table->dateTime('fin')->nullable();
            $table->json('tapis')->nullable();      // tableau [1,3,7]
            $table->integer('nombre')->nullable();
            $table->tinyInteger('qualite')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_triages');
    }
};
