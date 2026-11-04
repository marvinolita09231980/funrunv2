<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finishers', function (Blueprint $table): void {
            $table->string('finisher_rank')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('finishers', function (Blueprint $table): void {
            $table->string('finisher_rank')->nullable(false)->change();
        });
    }
};
