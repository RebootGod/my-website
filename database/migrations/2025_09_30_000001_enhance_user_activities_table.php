<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Enhanced User Activities Table for Advanced Analytics
     * Following workinginstruction.md: Professional structure with proper security
     */
    public function up(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            // Enhanced metadata columns for advanced analytics
            $table->json('geolocation')->nullable()->after('user_agent')
                ->comment('Geographic location data: country, city, coordinates, timezone');
            
            $table->json('device_fingerprint')->nullable()->after('geolocation')
                ->comment('Device identification: browser, OS, screen resolution, user agent details');
            
            $table->json('session_context')->nullable()->after('device_fingerprint')
                ->comment('Session information: session_id, referrer, duration, concurrent_sessions');
            
            $table->json('performance_metrics')->nullable()->after('session_context')
                ->comment('Performance data: load_time, response_time, bandwidth_usage, error_count');

            // Advanced analytics scoring columns
            $table->tinyInteger('risk_score')->unsigned()->default(0)->after('performance_metrics')
                ->comment('Security risk score (0-100): calculated based on suspicious patterns');
            
            $table->tinyInteger('engagement_score')->unsigned()->default(0)->after('risk_score')
                ->comment('User engagement score (0-100): calculated based on interaction patterns');
            
            $table->boolean('anomaly_flag')->default(false)->after('engagement_score')
                ->comment('Anomaly detection flag: true if activity is detected as anomalous');
            
            $table->timestamp('processed_at')->nullable()->after('anomaly_flag')
                ->comment('Timestamp when advanced analytics processing was completed');

            // Performance optimization indexes
            $table->index(['risk_score'], 'idx_user_activities_risk_score');
            $table->index(['engagement_score'], 'idx_user_activities_engagement_score');
            $table->index(['anomaly_flag'], 'idx_user_activities_anomaly_flag');
            $table->index(['processed_at'], 'idx_user_activities_processed_at');
            
            // Composite indexes for advanced queries
            $table->index(['user_id', 'risk_score', 'activity_at'], 'idx_user_risk_activity');
            $table->index(['activity_type', 'engagement_score', 'activity_at'], 'idx_type_engagement_activity');
            $table->index(['anomaly_flag', 'risk_score', 'activity_at'], 'idx_anomaly_risk_activity');
            
            // Geographic and device analysis indexes
            $table->index(['user_id', 'activity_at', 'processed_at'], 'idx_user_timeline_processed');
        });
    }

    /**
     * Reverse the migrations - Rollback strategy for safe deployment
     */
    public function down(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            // Drop indexes first to avoid constraint issues
            $table->dropIndex('idx_user_activities_risk_score');
            $table->dropIndex('idx_user_activities_engagement_score');
            $table->dropIndex('idx_user_activities_anomaly_flag');
            $table->dropIndex('idx_user_activities_processed_at');
            $table->dropIndex('idx_user_risk_activity');
            $table->dropIndex('idx_type_engagement_activity');
            $table->dropIndex('idx_anomaly_risk_activity');
            $table->dropIndex('idx_user_timeline_processed');

            // Drop columns
            $table->dropColumn([
                'geolocation',
                'device_fingerprint', 
                'session_context',
                'performance_metrics',
                'risk_score',
                'engagement_score',
                'anomaly_flag',
                'processed_at'
            ]);
        });
    }
};