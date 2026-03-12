<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('runs', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'start_time', 'distance_km']);
            $table->unique(['user_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::table('runs', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'start_time']);
            $table->unique(['user_id', 'start_time', 'distance_km']);
        });
    }
};
