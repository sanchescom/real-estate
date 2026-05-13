<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpp_series', function (Blueprint $table): void {
            $table->id();
            $table->string('country_code', 5);
            $table->string('covered_area', 2);
            $table->string('property_type', 2);
            $table->string('vintage', 1);
            $table->string('compiling_org', 1);
            $table->string('priced_unit', 1);
            $table->string('seasonal_adj', 1);
            $table->string('unit_measure', 100);
            $table->string('title', 500)->nullable();
            $table->text('coverage')->nullable();
            $table->text('data_compilation')->nullable();
            $table->timestamps();

            $table->unique(
                ['country_code', 'covered_area', 'property_type', 'vintage', 'compiling_org', 'priced_unit', 'seasonal_adj'],
                'dpp_series_composite_unique',
            );
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpp_series');
    }
};
