<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('developer_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('claimed_at');
            $table->timestamp('expires_at');
            $table->string('status', 20)->default('active');
            $table->json('draft')->nullable();
            $table->timestamp('draft_saved_at')->nullable();
            $table->timestamp('expiry_warned_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // One claim per developer per task — the database enforces "no duplicate claiming".
            $table->unique(['task_id', 'developer_id']);
            $table->index(['status', 'expires_at']);
            $table->index(['developer_id', 'status']);
        });

        Schema::create('task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_claim_id')->constrained('task_claims')->cascadeOnDelete();
            $table->foreignId('developer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->json('answer');
            $table->string('answer_hash', 64)->index();
            $table->timestamp('submitted_at');
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('rejection_reason', 1000)->nullable();
            $table->string('revision_note', 1000)->nullable();
            $table->decimal('reward', 10, 2);
            $table->boolean('auto_approved')->default(false);
            $table->boolean('is_flagged')->default(false)->index();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['task_id', 'status']);
            $table->index(['developer_id', 'status']);
            $table->index(['status', 'submitted_at']);
        });

        Schema::create('task_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('developer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->unsignedTinyInteger('clarity')->nullable();
            $table->boolean('time_accurate')->nullable();
            $table->string('comment', 1000)->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'developer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reviews');
        Schema::dropIfExists('task_submissions');
        Schema::dropIfExists('task_claims');
    }
};
