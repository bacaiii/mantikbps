<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publication_team_assignment_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('publication_id')->constrained('publications')->cascadeOnDelete();

            $table->string('action');
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publication_team_assignment_histories');
    }
};