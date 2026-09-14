<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("role_configs", function (Blueprint $table) {
            $table->id();
            $table->string("role");
            $table
                ->foreign("role")
                ->references("role")
                ->on("roles")
                ->cascadeOnDelete();
            $table->string("key");
            $table->text("value");
            $table->timestamps();

            $table->unique(["role", "key"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("role_configs");
    }
};
