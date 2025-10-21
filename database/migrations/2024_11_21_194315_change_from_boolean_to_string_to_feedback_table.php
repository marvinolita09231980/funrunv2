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
        Schema::table('feedback', function (Blueprint $table) {
            $table->string('raise_awareness')->nullable(true)->change();
            $table->string('encouraged_healthy_lifestyle')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->string('raise_awareness')->nullable(false)->change();
            $table->string('encouraged_healthy_lifestyle')->nullable(false)->change();
        });
    }
};
