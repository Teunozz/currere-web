<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pace_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained()->cascadeOnDelete();
            $table->integer('kilometer_number');
            $table->integer('split_time_seconds');
            $table->integer('pace_seconds_per_km');
            $table->boolean('is_partial')->default(false);
            $table->decimal('partial_distance_km', 5, 3)->nullable();

            $table->index(['run_id', 'kilometer_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pace_splits');
    }
};
