<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_subscriptions', function (Blueprint $table) {
            $table->foreignUuid('package_price_id')->nullable()->constrained('package_prices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['package_price_id']);
            $table->dropColumn('package_price_id');
        });
    }
};
