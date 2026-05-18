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

        Schema::table('users', function (Blueprint $table) {
            $table->string('firstname', 100)->nullable()->after('email');
            $table->string('lastname', 100)->nullable()->after('firstname');
        });

        Schema::create('enqueteurs', function (Blueprint $table) {
            $table->id();
            $table->string('poste', 100);
            $table->string('travail', 100);
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes(); // suppression douce
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviewers');
    }
};
