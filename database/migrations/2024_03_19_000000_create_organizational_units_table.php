<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizational_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['sector', 'division']);
            $table->foreignId('parent_id')->nullable()->constrained('organizational_units')->onDelete('cascade');
            $table->timestamps();
        });

        // Insert sample data
        // First, insert sectors
        DB::table('organizational_units')->insert([
            ['name' => 'Admin', 'type' => 'sector', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Research', 'type' => 'sector', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Development', 'type' => 'sector', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Get the sector IDs
        $researchId = DB::table('organizational_units')->where('name', 'Research')->first()->id;
        $developmentId = DB::table('organizational_units')->where('name', 'Development')->first()->id;

        // Insert divisions
        DB::table('organizational_units')->insert([
            ['name' => 'Division 1', 'type' => 'division', 'parent_id' => $researchId, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Division 2', 'type' => 'division', 'parent_id' => $researchId, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Division 1', 'type' => 'division', 'parent_id' => $developmentId, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Division 2', 'type' => 'division', 'parent_id' => $developmentId, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('organizational_units');
    }
}; 