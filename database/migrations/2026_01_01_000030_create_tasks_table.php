<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('task_categories')->restrictOnDelete();
            $table->string('title');
            $table->string('type', 40);
            $table->unsignedInteger('total_tasks')->default(0);
            $table->unsignedInteger('slots_per_task')->default(1);
            $table->decimal('reward', 10, 2);
            $table->decimal('platform_fee', 10, 2);
            $table->decimal('total_budget', 14, 2);
            $table->string('status', 20)->default('pending_approval')->index();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('task_categories')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('task_batches')->nullOnDelete();
            $table->foreignId('template_id')->nullable()->constrained('task_templates')->nullOnDelete();
            $table->string('type', 40)->index();
            $table->string('title');
            $table->text('description');
            $table->text('instructions');
            $table->json('payload')->nullable();
            $table->text('answer_format')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->index();
            // Money: DECIMAL only. reward = developer payout per slot, platform_fee = commission per slot.
            $table->decimal('reward', 10, 2)->index();
            $table->decimal('platform_fee', 10, 2);
            $table->decimal('commission_percent', 5, 2);
            $table->decimal('total_budget', 14, 2);
            $table->decimal('escrow_balance', 14, 2)->default(0);
            $table->unsignedInteger('available_slots');
            $table->unsignedInteger('reserved_slots')->default(0);
            $table->unsignedInteger('completed_slots')->default(0);
            $table->string('difficulty', 10)->default('easy')->index();
            $table->json('required_skills')->nullable();
            $table->string('status', 20)->default('pending_approval')->index();
            $table->timestamp('deadline')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejection_reason', 1000)->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->index(['status', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_batches');
    }
};
