<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('simulation_events');
    }

    public function down(): void
    {
        Schema::create('simulation_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('simulation_id');
            $table->integer('tick_number')->default(0);
            $table->string('event_type');
            $table->text('message');
            $table->timestamps();

            $table->foreign('simulation_id')->references('id')->on('simulations')->cascadeOnDelete();
        });
    }
};
