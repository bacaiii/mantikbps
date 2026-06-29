<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('team_template_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_template_id')->constrained('team_templates')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('assignment_role');
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['team_template_id', 'user_id', 'assignment_role'], 'team_template_member_role_unique');
            $table->index(['team_template_id', 'assignment_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_template_members');
        Schema::dropIfExists('team_templates');
    }
};
