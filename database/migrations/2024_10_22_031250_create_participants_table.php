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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('participantNumber');
            $table->string('firstName',100);
            $table->string('lastName',100);
            $table->string('middleInitial',1);
            $table->string('category',100);
            $table->string('subcategory',150);
            $table->string('shirtSize',10);
            $table->date('birthDate');
            $table->string('gender',1);
            $table->boolean('waiver');
            $table->string('distanceCategory',10);
            $table->string('referenceNumber',30)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
