<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpp_observations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('series_id')->constrained('dpp_series')->cascadeOnDelete();
            $table->string('frequency', 1);
            $table->string('period', 10);
            $table->decimal('value', 20, 4);
            $table->string('obs_status', 1)->nullable();
            $table->timestamps();

            $table->unique(['series_id', 'frequency', 'period'], 'dpp_obs_composite_unique');
            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpp_observations');
    }
};
