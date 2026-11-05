<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_allocations', function (Blueprint $table): void {
            $table->id();
            $table->string('categoryDescription')->unique();
            $table->unsignedInteger('allocated_participants')->default(0);
            $table->timestamps();
        });

        $categories = DB::table('subcategories')
            ->whereNotNull('categoryDescription')
            ->select('categoryDescription', DB::raw('SUM(nop) as allocated_participants'))
            ->groupBy('categoryDescription')
            ->get();

        foreach ($categories as $category) {
            DB::table('category_allocations')->insert([
                'categoryDescription' => $category->categoryDescription,
                'allocated_participants' => max(0, (int) $category->allocated_participants),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_allocations');
    }
};
