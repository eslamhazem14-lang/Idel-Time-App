<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per payout received from the ad network (e.g. Google's monthly payment).
        Schema::create('ad_payouts', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('share_percent', 5, 2);
            $table->decimal('developer_pool', 12, 2);
            $table->decimal('distributed_amount', 12, 2);
            $table->unsignedInteger('view_count');
            $table->unsignedInteger('developer_count');
            $table->string('reference', 120)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // One row per rewarded-ad impression a developer starts on the Watch & earn page.
        Schema::create('ad_views', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('idle_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ad_payout_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30);
            $table->string('status', 20)->default('started');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['status', 'completed_at']);
            $table->index(['user_id', 'status', 'completed_at']);
            $table->index(['user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_views');
        Schema::dropIfExists('ad_payouts');
    }
};
