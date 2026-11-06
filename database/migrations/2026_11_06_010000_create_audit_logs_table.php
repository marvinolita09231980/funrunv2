<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event', 20)->index();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('actor_name')->nullable();
            $table->string('actor_username')->nullable()->index();
            $table->unsignedBigInteger('impersonated_user_id')->nullable();
            $table->string('impersonated_name')->nullable();
            $table->string('impersonated_username')->nullable();
            $table->string('auditable_type')->index();
            $table->string('auditable_id')->nullable();
            $table->string('subject_label')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
