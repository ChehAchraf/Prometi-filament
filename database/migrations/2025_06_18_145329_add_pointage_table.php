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
        Schema::create('pointages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->date('date');
            $table->string('status');
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->decimal('heures_travaillees', 5, 2)->nullable();
            $table->decimal('heures_supplementaires', 5, 2)->default(0);
            $table->boolean('heures_supplementaires_approuvees')->default(false);
            $table->boolean('is_jour_ferie')->default(false);
            $table->float('coefficient')->default(1.0);
            $table->text('commentaire')->nullable();
            $table->timestamps();

            // Add unique constraint to prevent duplicate attendance records
            $table->unique(['user_id', 'project_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pointages');
    }
};
