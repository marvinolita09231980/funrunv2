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
        Schema::table('participants', function (Blueprint $table) {
             $table->boolean('pwd')->default(false)->after('shirtSize');
             $table->boolean('rpwuds')->default(false)->after('pwd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
             $table->dropColumn(['pwd', 'rpwuds']);
        });
    }
};
