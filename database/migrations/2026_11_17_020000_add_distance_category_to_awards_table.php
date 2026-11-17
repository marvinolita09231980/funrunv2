<?php

use App\Models\Award;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('awards', function (Blueprint $table): void {
            $table->string('distance_category')
                ->default(Award::DISTANCE_5KM)
                ->after('category')
                ->index();
        });

        DB::table('awards')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->eachById(function (object $award): void {
                DB::table('awards')
                    ->where('id', $award->id)
                    ->update([
                        'distance_category' => $this->distanceFor($award->name),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('awards', function (Blueprint $table): void {
            $table->dropColumn('distance_category');
        });
    }

    private function distanceFor(string $name): string
    {
        $normalized = strtolower((string) preg_replace('/\s+/', '', trim($name)));

        if (str_contains($normalized, '10k')) {
            return Award::DISTANCE_10KM;
        }

        if (str_contains($normalized, '3k')) {
            return Award::DISTANCE_3KM;
        }

        return Award::DISTANCE_5KM;
    }
};
