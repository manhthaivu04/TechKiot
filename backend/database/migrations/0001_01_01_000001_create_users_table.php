<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the default Laravel users scaffold.
 * Uses UUID primary key and includes all KiotTay-specific columns.
 * Runs after 0001_01_01_000000_create_restaurants_table (FK to restaurants).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id')->nullable();
            $table->foreign('restaurant_id')
                  ->references('id')
                  ->on('restaurants')
                  ->nullOnDelete();

            $table->string('name', 150);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->enum('role', [
                'SUPER_ADMIN', 'OWNER', 'MANAGER', 'WAITER', 'KITCHEN', 'CASHIER',
            ])->default('OWNER');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
