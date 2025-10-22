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
        // Add indexes to todos table for frequently queried fields
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'idx_todos_user_status');
            $table->index(['assignee'], 'idx_todos_assignee');
            $table->index(['priority'], 'idx_todos_priority');
            $table->index(['type'], 'idx_todos_type');
            $table->index(['created_at'], 'idx_todos_created_at');
            $table->index(['updated_at'], 'idx_todos_updated_at');
        });

        // Add indexes to projects table
        Schema::table('taskit_projects', function (Blueprint $table) {
            $table->index(['owner_id'], 'idx_projects_owner');
            $table->index(['is_active'], 'idx_projects_active');
            $table->index(['created_at'], 'idx_projects_created_at');
        });

        // Add indexes to todo_comments table
        Schema::table('taskit_todo_comments', function (Blueprint $table) {
            $table->index(['todo_id'], 'idx_comments_todo');
            $table->index(['user_id'], 'idx_comments_user');
            $table->index(['created_at'], 'idx_comments_created_at');
        });

        // Add indexes to todo_attachments table
        Schema::table('taskit_todo_attachments', function (Blueprint $table) {
            $table->index(['todo_id'], 'idx_attachments_todo');
            $table->index(['user_id'], 'idx_attachments_user');
            $table->index(['created_at'], 'idx_attachments_created_at');
        });

        // Add indexes to notifications table
        Schema::table('taskit_notifications', function (Blueprint $table) {
            $table->index(['user_id'], 'idx_notifications_user');
            $table->index(['user_id', 'read_at'], 'idx_notifications_user_read');
            $table->index(['created_at'], 'idx_notifications_created_at');
        });

        // Add indexes to companies table
        Schema::table('taskit_companies', function (Blueprint $table) {
            $table->index(['subscription_type'], 'idx_companies_subscription');
            $table->index(['subscription_status'], 'idx_companies_subscription_status');
        });

        // Add indexes to company_messages table
        Schema::table('company_messages', function (Blueprint $table) {
            $table->index(['company_id'], 'idx_messages_company');
            $table->index(['sender_id'], 'idx_messages_sender');
            $table->index(['company_id', 'created_at'], 'idx_messages_company_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from todos table
        Schema::table('taskit_todos', function (Blueprint $table) {
            $table->dropIndex('idx_todos_user_status');
            $table->dropIndex('idx_todos_assignee');
            $table->dropIndex('idx_todos_priority');
            $table->dropIndex('idx_todos_type');
            $table->dropIndex('idx_todos_created_at');
            $table->dropIndex('idx_todos_updated_at');
        });

        // Remove indexes from projects table
        Schema::table('taskit_projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_owner');
            $table->dropIndex('idx_projects_active');
            $table->dropIndex('idx_projects_created_at');
        });

        // Remove indexes from todo_comments table
        Schema::table('taskit_todo_comments', function (Blueprint $table) {
            $table->dropIndex('idx_comments_todo');
            $table->dropIndex('idx_comments_user');
            $table->dropIndex('idx_comments_created_at');
        });

        // Remove indexes from todo_attachments table
        Schema::table('taskit_todo_attachments', function (Blueprint $table) {
            $table->dropIndex('idx_attachments_todo');
            $table->dropIndex('idx_attachments_user');
            $table->dropIndex('idx_attachments_created_at');
        });

        // Remove indexes from notifications table
        Schema::table('taskit_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user');
            $table->dropIndex('idx_notifications_user_read');
            $table->dropIndex('idx_notifications_created_at');
        });

        // Remove indexes from companies table
        Schema::table('taskit_companies', function (Blueprint $table) {
            $table->dropIndex('idx_companies_subscription');
            $table->dropIndex('idx_companies_subscription_status');
        });

        // Remove indexes from company_messages table
        Schema::table('company_messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_company');
            $table->dropIndex('idx_messages_sender');
            $table->dropIndex('idx_messages_company_created');
        });
    }
};