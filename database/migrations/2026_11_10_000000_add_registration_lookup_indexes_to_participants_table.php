<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->index(
                ['year', 'firstName', 'lastName', 'birthDate'],
                'participants_registration_identity_idx',
            );
            $table->index(
                ['categoryDescription', 'year'],
                'participants_category_year_idx',
            );
            $table->index(
                ['year', 'participantNumber'],
                'participants_registration_group_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table): void {
            $table->dropIndex('participants_registration_identity_idx');
            $table->dropIndex('participants_category_year_idx');
            $table->dropIndex('participants_registration_group_idx');
        });
    }
};
