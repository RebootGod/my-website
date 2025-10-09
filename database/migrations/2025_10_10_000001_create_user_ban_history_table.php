<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Track all ban/suspension/reactivation events for users.
     * Provides audit trail and timeline for administrative actions.
     */
    public function up(): void
    {
        Schema::create('user_ban_history', function (Blueprint $table) {
            $table->id();
            
            // Target user who was affected
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('User who was banned/suspended/reactivated');
            
            // Type of action performed
            $table->enum('action_type', ['ban', 'unban', 'suspend', 'activate'])
                ->comment('Type of administrative action');
            
            // Reason for the action
            $table->text('reason')
                ->comment('Explanation for the ban/suspension/reactivation');
            
            // Admin who performed the action
            $table->foreignId('performed_by')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Admin user who performed this action');
            
            // Duration for temporary suspensions (in days, null = permanent)
            $table->integer('duration')
                ->nullable()
                ->comment('Suspension duration in days (null = permanent ban)');
            
            // IP address of the admin who performed the action
            $table->string('admin_ip', 45)
                ->nullable()
                ->comment('IP address of admin performing action');
            
            // Additional metadata (can store JSON data)
            $table->json('metadata')
                ->nullable()
                ->comment('Additional context or evidence');
            
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index('user_id', 'idx_user_ban_history_user');
            $table->index('performed_by', 'idx_user_ban_history_admin');
            $table->index('action_type', 'idx_user_ban_history_type');
            $table->index('created_at', 'idx_user_ban_history_date');
            $table->index(['user_id', 'action_type'], 'idx_user_ban_history_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ban_history');
    }
};
