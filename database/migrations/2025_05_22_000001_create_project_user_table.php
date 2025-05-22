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
        // Create project_user pivot table
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['project_id', 'user_id']);
        });
        
        // Add status field to users table if it doesn't exist
        if (!Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('status')->default('actif');
            });
        }
        
        // Add new fields to projects table
        if (!Schema::hasColumn('projects', 'start_date')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
            });
        }
        
        // Add new fields to pointages table
        if (!Schema::hasColumn('pointages', 'is_jour_ferie')) {
            Schema::table('pointages', function (Blueprint $table) {
                $table->boolean('is_jour_ferie')->default(false);
                $table->float('coefficient')->default(1.0);
                $table->text('commentaire')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_user');
        
        if (Schema::hasColumn('users', 'status')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
        
        if (Schema::hasColumn('projects', 'start_date')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn(['start_date', 'end_date']);
            });
        }
        
        if (Schema::hasColumn('pointages', 'is_jour_ferie')) {
            Schema::table('pointages', function (Blueprint $table) {
                $table->dropColumn(['is_jour_ferie', 'coefficient', 'commentaire']);
            });
        }
    }
};
