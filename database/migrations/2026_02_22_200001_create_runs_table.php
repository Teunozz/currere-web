<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->decimal('distance_km', 8, 3);
            $table->integer('duration_seconds');
            $table->integer('steps')->nullable();
            $table->integer('avg_heart_rate')->nullable();
            $table->integer('avg_pace_seconds_per_km')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'start_time', 'distance_km']);
            $table->index(['user_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runs');
    }
};
