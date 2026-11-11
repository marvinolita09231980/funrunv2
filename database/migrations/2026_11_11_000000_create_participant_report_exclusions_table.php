<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_report_exclusions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('participant_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('report_key', 80);
            $table->foreignId('excluded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['participant_id', 'year', 'report_key'],
                'participant_report_exclusion_unique',
            );
            $table->index(['year', 'report_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_report_exclusions');
    }
};
