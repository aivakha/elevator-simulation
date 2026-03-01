<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('simulation_runs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('simulation_id');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('total_ticks')->default(0);
            $table->json('metrics_json')->nullable();
            $table->timestamps();

            $table->foreign('simulation_id')->references('id')->on('simulations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_runs');
    }
};
