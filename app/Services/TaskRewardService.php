<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Enums\WalletBucket;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\User;

/**
 * Money movements triggered by a review decision. Every method must be
 * called inside a transaction with the task row locked.
 */
class TaskRewardService
{
    public function __construct(private readonly WalletService $wallets) {}

    /**
     * Pay an approved submission out of the task escrow:
     *  developer pending → developer available (reward)
     *  requester escrow  → spent (reward + fee)
     *  platform wallet   ← fee
     */
    public function payApproved(TaskSubmission $submission, Task $lockedTask, ?User $actor = null): void
    {
        $reward = $submission->reward;
        $fee = $lockedTask->platform_fee;
        $unit = $reward->add($fee);
        $developerWallet = $this->wallets->walletFor($submission->developer);
        $requesterWallet = $this->wallets->walletFor($lockedTask->requester);

        $this->wallets->post($developerWallet, TransactionType::TaskReward, WalletBucket::Pending, $reward->negate(),
            "{$lockedTask->title} — released after approval", $submission, actor: $actor);
        $this->wallets->post($developerWallet, TransactionType::TaskReward, WalletBucket::Available, $reward,
            $lockedTask->title, $submission, actor: $actor, lifetime: 'lifetime_earnings');

        $this->wallets->post($requesterWallet, TransactionType::TaskPayment, WalletBucket::Pending, $unit->negate(),
            "Approved submission #{$submission->id} — {$lockedTask->title}", $submission, actor: $actor, lifetime: 'lifetime_spending',
            meta: ['reward' => $reward->toDecimal(), 'platform_fee' => $fee->toDecimal()]);

        if ($fee->isPositive()) {
            $this->wallets->post($this->wallets->platformWallet(), TransactionType::PlatformFee, WalletBucket::Available, $fee,
                "Commission on submission #{$submission->id}", $submission, actor: $actor);
        }

        $lockedTask->escrow_balance = $lockedTask->escrow_balance->subtract($unit);
        $lockedTask->reserved_slots = max(0, $lockedTask->reserved_slots - 1);
        $lockedTask->completed_slots++;
        $lockedTask->save();
    }

    /** Remove a submission's reward from the developer's pending balance (rejection / revision). */
    public function reversePending(TaskSubmission $submission, string $description, ?User $actor = null): void
    {
        $this->wallets->post($this->wallets->walletFor($submission->developer), TransactionType::TaskReward, WalletBucket::Pending,
            $submission->reward->negate(), $description, $submission, TransactionStatus::Failed, actor: $actor);
    }

    /** Put a submission's reward back into pending (a rejected submission re-opened by an admin). */
    public function restorePending(TaskSubmission $submission, string $description, ?User $actor = null): void
    {
        $this->wallets->post($this->wallets->walletFor($submission->developer), TransactionType::TaskReward, WalletBucket::Pending,
            $submission->reward, $description, $submission, TransactionStatus::Pending, actor: $actor);
    }

    /**
     * Reverse a paid approval (admin override): claw back the reward from the
     * developer, the commission from the platform, and refund the requester.
     */
    public function clawBack(TaskSubmission $submission, Task $lockedTask, User $admin, string $reason): void
    {
        $reward = $submission->reward;
        $fee = $lockedTask->platform_fee;

        $this->wallets->post($this->wallets->walletFor($submission->developer), TransactionType::Adjustment, WalletBucket::Available,
            $reward->negate(), "Reversal of approved submission #{$submission->id}: {$reason}", $submission,
            actor: $admin, lifetime: 'lifetime_earnings', lifetimeSign: -1);

        if ($fee->isPositive()) {
            $this->wallets->post($this->wallets->platformWallet(), TransactionType::PlatformFee, WalletBucket::Available,
                $fee->negate(), "Commission reversed for submission #{$submission->id}", $submission, actor: $admin, allowNegative: true);
        }

        $this->wallets->post($this->wallets->walletFor($lockedTask->requester), TransactionType::Refund, WalletBucket::Available,
            $reward->add($fee), "Refund for reversed submission #{$submission->id}", $submission,
            actor: $admin, lifetime: 'lifetime_spending', lifetimeSign: -1);

        $lockedTask->completed_slots = max(0, $lockedTask->completed_slots - 1);
        $lockedTask->available_slots = max($lockedTask->completed_slots + $lockedTask->reserved_slots, $lockedTask->available_slots - 1);
        $lockedTask->save();
    }
}
