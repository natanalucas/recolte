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
        Schema::table('code_traca', function (Blueprint $table) {
            $table->foreignId('societe_id')->nullable()->after('parcelle_id')->constrained('societes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('code_traca', function (Blueprint $table) {
            //
        });
    }
};
