<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('kalamboan_registration_enabled')->default(true);
            $table->timestamps();
        });

        DB::table('registration_settings')->insert([
            'id' => 1,
            'kalamboan_registration_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_settings');
    }
};
