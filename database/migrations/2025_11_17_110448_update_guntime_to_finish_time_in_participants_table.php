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
           
            $table->renameColumn('guntime', 'finish_time');
        });

        Schema::table('finishers', function (Blueprint $table) {
            
            $table->dateTime('finish_time')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finishers', function (Blueprint $table) {
            
            $table->string('finish_time')->change();
         
            $table->renameColumn('finish_time', 'guntime');
        });
    }
};
