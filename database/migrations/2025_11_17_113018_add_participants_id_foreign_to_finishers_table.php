<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finishers', function (Blueprint $table) {
            // Only add column if it doesn't exist
            if (!Schema::hasColumn('finishers', 'participants_id')) {
                $table->unsignedBigInteger('participants_id')->after('id');

                // Add foreign key
                $table->foreign('participants_id')
                    ->references('id')
                    ->on('participants')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('finishers', function (Blueprint $table) {
            if (Schema::hasColumn('finishers', 'participants_id')) {
                $table->dropForeign(['participants_id']);
                $table->dropColumn('participants_id');
            }
        });
    }
};
