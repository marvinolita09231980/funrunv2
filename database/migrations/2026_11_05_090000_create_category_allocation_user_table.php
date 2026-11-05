<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_allocation_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_allocation_id')
                ->constrained('category_allocations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['category_allocation_id', 'user_id']);
        });

        $officers = DB::table('users')
            ->join('model_has_roles', function ($join): void {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('roles.name', ['administrative officer', 'ao'])
            ->whereNotNull('users.administered_category')
            ->where('users.administered_category', '!=', '')
            ->select('users.id', 'users.administered_category')
            ->distinct()
            ->get();

        foreach ($officers as $officer) {
            $categoryAllocationId = DB::table('category_allocations')
                ->where('categoryDescription', $officer->administered_category)
                ->value('id');

            if (! $categoryAllocationId) {
                $categoryAllocationId = DB::table('category_allocations')->insertGetId([
                    'categoryDescription' => $officer->administered_category,
                    'allocated_participants' => (int) DB::table('subcategories')
                        ->where('categoryDescription', $officer->administered_category)
                        ->sum('nop'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('category_allocation_user')->insertOrIgnore([
                'category_allocation_id' => $categoryAllocationId,
                'user_id' => $officer->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $assignedTotal = (int) DB::table('category_allocations')
            ->sum('allocated_participants');

        DB::table('overall_allocations')
            ->where('id', 1)
            ->where('total_participants', '<', $assignedTotal)
            ->update([
                'total_participants' => $assignedTotal,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('category_allocation_user');
    }
};
