<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('awards', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('finishers', function (Blueprint $table): void {
            $table->foreignId('award_id')
                ->nullable()
                ->after('finisher_rank')
                ->constrained('awards')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finishers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('award_id');
        });

        Schema::dropIfExists('awards');
    }
};
