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
            // Change column types to TEXT to accommodate larger token sizes
            $table->text('google_access_token')->change();
            $table->text('google_refresh_token')->change();
            
            // Also fix the calendar_id field which appears to be causing issues
            $table->string('google_calendar_id', 255)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revert back to original column sizes
            $table->string('google_access_token')->change();
            $table->string('google_refresh_token')->change();
            $table->string('google_calendar_id')->change();
        });
    }
};
