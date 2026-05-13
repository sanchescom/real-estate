<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spp_observations', function (Blueprint $table): void {
            $table->id();
            $table->string('country_code', 5);
            $table->string('value_type', 1);
            $table->string('unit_measure', 3);
            $table->char('period', 7);
            $table->decimal('value', 20, 4);
            $table->string('obs_status', 1)->nullable();
            $table->timestamps();

            $table->unique(['country_code', 'value_type', 'unit_measure', 'period'], 'spp_composite_unique');
            $table->index('country_code');
            $table->index('period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spp_observations');
    }
};
