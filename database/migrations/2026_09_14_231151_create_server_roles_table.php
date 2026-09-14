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
        Schema::create('server_roles', function (Blueprint $table) {
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->foreign('role')->references('role')->on('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['server_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_roles');
    }
};
