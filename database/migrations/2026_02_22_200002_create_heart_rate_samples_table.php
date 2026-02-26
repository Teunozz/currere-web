<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heart_rate_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained()->cascadeOnDelete();
            $table->dateTime('timestamp');
            $table->integer('bpm');

            $table->index(['run_id', 'timestamp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heart_rate_samples');
    }
};
