<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('finishers', function (Blueprint $table) {
             if (!Schema::hasColumn('finishers', 'created_by')) {
                $table->string('created_by')->nullable()->after('id');
             }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finishers', function (Blueprint $table) {
             $table->dropForeign(['created_by']);
             $table->dropColumn('created_by');
             $table->string('created_by')->nullable();
        });
    }
};
