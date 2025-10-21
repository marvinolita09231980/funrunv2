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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->string('participantNumber');
            $table->string('rate')->nullable();
            $table->string('aware_of_funrun')->nullable();
            $table->string('inspired')->nullable();
            $table->boolean('raise_awareness')->default(true);
            $table->longtext('contribute_to_your_understanding')->nullable();
            $table->boolean('encouraged_healthy_lifestyle')->default(false);
            $table->longtext('which_part_enjoy')->nullable();
            $table->longtext('recommendation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
