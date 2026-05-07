<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_features', function (Blueprint $table) {
            $table->uuid('package_id');
            $table->uuid('feature_id');

            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
            $table->foreign('feature_id')->references('id')->on('features')->cascadeOnDelete();

            $table->primary(['package_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_features');
    }
};
