<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            // NULL user_id + type=platform is the platform revenue wallet.
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('user')->index();
            $table->string('currency', 3)->default('USD');
            $table->decimal('balance', 14, 2)->default(0);
            $table->decimal('pending_balance', 14, 2)->default(0);
            $table->decimal('lifetime_earnings', 14, 2)->default(0);
            $table->decimal('lifetime_spending', 14, 2)->default(0);
            $table->timestamps();
        });

        // Append-only ledger. Rows are never updated or deleted by the application.
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->string('type', 30)->index();
            $table->string('bucket', 12)->default('available');
            $table->decimal('amount', 14, 2); // signed
            $table->decimal('balance_after', 14, 2);
            $table->nullableMorphs('reference');
            $table->string('description');
            $table->string('status', 20)->default('completed');
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('developer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('method', 30);
            $table->text('account_details'); // encrypted at rest
            $table->string('status', 20)->default('pending')->index();
            $table->string('gateway', 30)->default('manual');
            $table->string('gateway_reference')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('admin_note', 1000)->nullable();
            $table->timestamps();
        });

        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('method', 30);
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('gateway', 30)->default('manual');
            $table->string('gateway_reference')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('admin_note', 1000)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
