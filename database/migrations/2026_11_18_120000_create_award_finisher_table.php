<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_finisher', function (Blueprint $table): void {
            $table->foreignId('finisher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('award_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique('award_id');
            $table->unique(['finisher_id', 'award_id']);
        });

        DB::table('finishers')
            ->whereNotNull('award_id')
            ->orderBy('id')
            ->each(function (object $finisher): void {
                DB::table('award_finisher')->insertOrIgnore([
                    'finisher_id' => $finisher->id,
                    'award_id' => $finisher->award_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('award_finisher');
    }
};
