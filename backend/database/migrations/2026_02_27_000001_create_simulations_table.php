<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('simulations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('status')->default('draft');
            $table->json('config_json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulations');
    }
};
