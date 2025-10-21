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
        Schema::create('finishers', function (Blueprint $table) {
            $table->id();
            $table->string('participant_number');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_initial')->nullable();
            $table->string('category')->nullable();
            $table->string('subcategory')->nullable();
            $table->string('gender')->nullable();
            $table->string('distance_category')->nullable();
            $table->string('racebib')->unique();
            $table->string('guntime');
            $table->string('finisher_rank');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finishers');
    }
};
