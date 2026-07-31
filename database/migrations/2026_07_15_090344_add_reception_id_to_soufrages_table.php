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
        Schema::table('soufrages', function (Blueprint $table) {
            $table->foreignId('reception_id')
                  ->nullable()
                  ->constrained('fiche_receptions')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('soufrages', function (Blueprint $table) {
            $table->dropForeign(['reception_id']);
            $table->dropColumn('reception_id');
        });
    }
};
