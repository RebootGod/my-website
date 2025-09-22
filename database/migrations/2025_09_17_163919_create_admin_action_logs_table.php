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
        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();
            
            // Admin user who performed the action
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            
            // Action details
            $table->string('action', 100)->index(); // e.g., 'user_banned', 'role_changed', 'password_reset'
            $table->string('action_type', 50)->index(); // e.g., 'user_management', 'role_management', 'system'
            $table->text('description'); // Human-readable description
            
            // Target information (if applicable)
            $table->foreignId('target_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('target_type', 100)->nullable(); // e.g., 'user', 'role', 'movie', 'system'
            $table->unsignedBigInteger('target_id')->nullable(); // Generic target ID
            
            // Request context
            $table->string('ip_address', 45)->index(); // IPv4 and IPv6 support
            $table->text('user_agent')->nullable();
            $table->string('request_method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->string('request_url', 500)->nullable();
            
            // Additional data (JSON for flexibility)
            $table->json('metadata')->nullable(); // Store additional context data
            $table->json('old_values')->nullable(); // Store previous values for changes
            $table->json('new_values')->nullable(); // Store new values for changes
            
            // Security and compliance
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium')->index();
            $table->boolean('is_sensitive')->default(false)->index(); // Flag for sensitive operations
            $table->string('session_id', 100)->nullable(); // Track session for correlation
            
            // Status and processing
            $table->enum('status', ['success', 'failed', 'pending'])->default('success')->index();
            $table->text('error_message')->nullable(); // If action failed
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['admin_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['target_user_id', 'created_at']);
            $table->index(['severity', 'created_at']);
            $table->index(['is_sensitive', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_action_logs');
    }
};
