<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overall_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('total_participants')->default(0);
            $table->timestamps();
        });

        DB::table('overall_allocations')->insert([
            'id' => 1,
            'total_participants' => (int) DB::table('category_allocations')
                ->sum('allocated_participants'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('overall_allocations');
    }
};
