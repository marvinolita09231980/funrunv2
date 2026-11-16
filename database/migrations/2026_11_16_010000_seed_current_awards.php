<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const AWARDS = [
        '10K Finisher Female',
        '10K Finisher Male',
        '5K Finisher Female',
        '5K Finisher Male',
        'Oldest Female 5K Winner',
        'Oldest Female 10K Winner',
        'Oldest Male 10K Winner',
        'Oldest Male 5K Winner',
        'Youngest Female 10K Winner',
        'Youngest Female 5K Winner',
        'Youngest Male 10K Winner',
        'Youngest Male 5K Winner',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('awards')) {
            return;
        }

        DB::transaction(function (): void {
            foreach (self::AWARDS as $awardName) {
                $matches = DB::table('awards')
                    ->select(['id', 'name'])
                    ->get()
                    ->filter(fn (object $award): bool => $this->normalize($award->name) === $this->normalize($awardName));

                if ($matches->isEmpty()) {
                    DB::table('awards')->insert([
                        'name' => $awardName,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    continue;
                }

                $target = $matches->firstWhere('name', $awardName) ?? $matches->first();

                foreach ($matches as $match) {
                    if ((int) $match->id === (int) $target->id) {
                        continue;
                    }

                    if (Schema::hasColumn('finishers', 'award_id')) {
                        DB::table('finishers')
                            ->where('award_id', $match->id)
                            ->update(['award_id' => $target->id]);
                    }

                    DB::table('awards')->where('id', $match->id)->delete();
                }

                DB::table('awards')
                    ->where('id', $target->id)
                    ->update([
                        'name' => $awardName,
                        'is_active' => true,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        // Preserve reference records and existing finisher assignments on rollback.
    }

    private function normalize(string $value): string
    {
        return mb_strtolower((string) preg_replace('/\s+/', ' ', trim($value)));
    }
};
