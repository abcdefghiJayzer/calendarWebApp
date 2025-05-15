<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // First check if these columns exist and drop them if they do
            if (Schema::hasColumn('users', 'google_access_token')) {
                $table->dropColumn('google_access_token');
            }
            if (Schema::hasColumn('users', 'google_refresh_token')) {
                $table->dropColumn('google_refresh_token');
            }
            if (Schema::hasColumn('users', 'google_calendar_id')) {
                $table->dropColumn('google_calendar_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            // Re-add the columns with TEXT type for tokens to allow larger storage
            $table->text('google_access_token')->nullable();
            $table->text('google_refresh_token')->nullable();
            $table->string('google_calendar_id', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop the new columns
            $table->dropColumn('google_access_token');
            $table->dropColumn('google_refresh_token');
            $table->dropColumn('google_calendar_id');
            
            // Add back the original columns
            $table->string('google_access_token', 255)->nullable();
            $table->string('google_refresh_token', 255)->nullable();
            $table->string('google_calendar_id', 100)->nullable();
        });
    }
};
