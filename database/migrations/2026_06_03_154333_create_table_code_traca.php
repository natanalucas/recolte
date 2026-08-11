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
        Schema::create('code_traca', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::table('triages', function (Blueprint $table) {
            $table->foreignId('code_traca_id')->nullable()->constrained('code_traca')->nullOnDelete()->after('type_certification_id');
        });
        Schema::table('soufrages', function (Blueprint $table) {
            $table->foreignId('code_traca_id')->nullable()->constrained('code_traca')->nullOnDelete()->after('fin');
        });

        Schema::table('triages', function (Blueprint $table) {
            // Suppression de la colonne
            if (Schema::hasColumn('triages', 'code')) {
                Schema::table('triages', function (Blueprint $table) {
                    $table->dropColumn('code');
                });
            }
        });

        Schema::table('soufrages', function (Blueprint $table) {
            // Suppression de la colonne
            if (Schema::hasColumn('triages', 'code')) {
                Schema::table('triages', function (Blueprint $table) {
                    $table->dropColumn('code');
                });
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('code_traca');
    }
};
