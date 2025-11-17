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
           
            if (Schema::hasColumn('finishers', 'participant_number')) {
                $table->renameColumn('participant_number', 'participantNumber');
            }

            if (Schema::hasColumn('finishers', 'first_name')) {
                $table->renameColumn('first_name', 'firstName');
            }

            if (Schema::hasColumn('finishers', 'last_name')) {
                $table->renameColumn('last_name', 'lastName');
            }

            if (Schema::hasColumn('finishers', 'middle_initial')) {
                $table->renameColumn('middle_initial', 'middleInitial');
            }

            if (Schema::hasColumn('finishers', 'category')) {
                $table->renameColumn('category', 'categoryDescription');
            }

            if (Schema::hasColumn('finishers', 'subcategory')) {
                $table->renameColumn('subcategory', 'subDescription');
            }

            if (Schema::hasColumn('finishers', 'distance_category')) {
                $table->renameColumn('distance_category', 'distanceCategory');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finishers', function (Blueprint $table) {

            if (Schema::hasColumn('finishers', 'participantNumber')) {
                $table->renameColumn('participantNumber', 'participant_number');
            }

            if (Schema::hasColumn('finishers', 'firstName')) {
                $table->renameColumn('firstName', 'first_name');
            }

            if (Schema::hasColumn('finishers', 'lastName')) {
                $table->renameColumn('lastName', 'last_name');
            }

            if (Schema::hasColumn('finishers', 'middleInitial')) {
                $table->renameColumn('middleInitial', 'middle_initial');
            }

            if (Schema::hasColumn('finishers', 'categoryDescription')) {
                $table->renameColumn('categoryDescription', 'category');
            }

            if (Schema::hasColumn('finishers', 'subDescription')) {
                $table->renameColumn('subDescription', 'subcategory');
            }

            if (Schema::hasColumn('finishers', 'distanceCategory')) {
                $table->renameColumn('distanceCategory', 'distance_category');
            }
        });
    }
};
