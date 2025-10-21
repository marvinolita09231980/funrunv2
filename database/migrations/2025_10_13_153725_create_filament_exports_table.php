<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::create('filament_exports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->nullable();
            $table->string('status')->default('pending');
            $table->string('file_name')->nullable();
            $table->string('file_disk')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('total_rows')->nullable();
            $table->unsignedBigInteger('processed_rows')->nullable();
            $table->text('filters')->nullable();
            $table->text('columns')->nullable();
            $table->text('options')->nullable();
            $table->text('exception')->nullable();
           
            $table->morphs('user');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filament_exports');
    }
};
