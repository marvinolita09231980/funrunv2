<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback', function (Blueprint $table): void {
            $table->index(
                ['year', 'firstName', 'lastName', 'birthDate'],
                'feedback_identity_year_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table): void {
            $table->dropIndex('feedback_identity_year_idx');
        });
    }
};
