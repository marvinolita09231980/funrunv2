<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('assigned_category')->nullable()->after('administered_category');
            $table->string('created_by')->nullable()->after('assigned_category');
        });

        $assignments = DB::table('subcategories')
            ->whereNotNull('username')
            ->whereNotNull('categoryDescription')
            ->select(
                'username',
                DB::raw('MIN(categoryDescription) as categoryDescription'),
                DB::raw('COUNT(DISTINCT categoryDescription) as category_count'),
            )
            ->groupBy('username')
            ->get();

        foreach ($assignments as $assignment) {
            if ((int) $assignment->category_count !== 1) {
                continue;
            }

            DB::table('users')
                ->where('username', $assignment->username)
                ->update(['assigned_category' => $assignment->categoryDescription]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['assigned_category', 'created_by']);
        });
    }
};
