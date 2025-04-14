<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If users table doesn't exist, create it
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }
        // If users table exists but doesn't have these columns, add them
        else {
            if (!Schema::hasColumn('users', 'email')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('email')->unique();
                });
            }
            if (!Schema::hasColumn('users', 'password')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('password');
                });
            }
            if (!Schema::hasColumn('users', 'remember_token')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->rememberToken();
                });
            }
        }
    }

    public function down(): void
    {
        // The down method is intentionally left empty to prevent data loss
    }
};
