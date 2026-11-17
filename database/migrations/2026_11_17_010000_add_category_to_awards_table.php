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
            $table->string('category')
                ->default(Award::CATEGORY_SPECIAL_AWARDS)
                ->after('name')
                ->index();
        });

        DB::table('awards')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->eachById(function (object $award): void {
                DB::table('awards')
                    ->where('id', $award->id)
                    ->update([
                        'category' => $this->categoryFor($award->name),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('awards', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }

    private function categoryFor(string $name): string
    {
        $isRacePlacement = preg_match('/^(?:5|10)\s*km?-run\s+\d+/i', trim($name)) === 1;
        $isExistingFinisherAward = preg_match('/^(?:5|10)K Finisher (?:Female|Male)$/i', trim($name)) === 1;

        return ($isRacePlacement || $isExistingFinisherAward)
            ? Award::CATEGORY_TOP_FINISHERS
            : Award::CATEGORY_SPECIAL_AWARDS;
    }
};
