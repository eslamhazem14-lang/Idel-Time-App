<?php

namespace App\Services;

use App\Enums\FraudFlagStatus;
use App\Enums\FraudFlagType;
use App\Enums\SubmissionStatus;
use App\Models\FraudFlag;
use App\Models\TaskSubmission;
use App\Models\User;
use App\Notifications\AdminAlertNotification;
use App\Support\AdminNotifier;

/**
 * Heuristic fraud signals. Signals only raise flags and a fraud score for
 * admin review — nobody is banned automatically.
 */
class FraudDetectionService
{
    public function inspectRegistration(User $user, ?string $ip): void
    {
        $twin = User::query()->where('email_normalized', $user->email_normalized)->whereKeyNot($user->id)->first();
        if ($twin) {
            $this->flag($user, FraudFlagType::SimilarEmail, ['matches_user_id' => $twin->id, 'normalized' => $user->email_normalized]);
        }
        $this->inspectIp($user, $ip);
    }

    public function inspectIp(User $user, ?string $ip): void
    {
        if (! $ip || in_array($ip, ['127.0.0.1', '::1'], true)) {
            return;
        }
        $accounts = User::query()->whereKeyNot($user->id)
            ->where(fn ($q) => $q->where('registration_ip', $ip)->orWhere('last_login_ip', $ip))
            ->pluck('id');

        if ($accounts->count() >= (int) config('platform.fraud.shared_ip_threshold')) {
            $this->flag($user, FraudFlagType::SharedIp, ['ip' => $ip, 'other_accounts' => $accounts->take(20)->all()]);
        }
    }

    public function inspectSubmission(TaskSubmission $submission): void
    {
        $submission->loadMissing('task', 'developer');
        $task = $submission->task;
        $developer = $submission->developer;
        $cfg = config('platform.fraud');
        $flagged = false;

        // Suspiciously fast completion
        $minimumSeconds = (int) ($task->estimated_minutes * 60 * $cfg['fast_completion_ratio']);
        if ($submission->time_spent_seconds < $minimumSeconds) {
            $this->flag($developer, FraudFlagType::FastCompletion, [
                'submission_id' => $submission->id, 'seconds' => $submission->time_spent_seconds, 'estimated_minutes' => $task->estimated_minutes,
            ]);
            $flagged = true;
        }

        if ($task->handler()->fingerprint($submission->answer) !== null) {
            // Identical answer already submitted on the same task by someone else
            $duplicate = TaskSubmission::query()->where('task_id', $task->id)->where('answer_hash', $submission->answer_hash)
                ->where('developer_id', '!=', $developer->id)->first();
            if ($duplicate) {
                $this->flag($developer, FraudFlagType::DuplicateSubmission, [
                    'submission_id' => $submission->id, 'duplicate_of' => $duplicate->id, 'other_developer_id' => $duplicate->developer_id,
                ]);
                $flagged = true;
            }

            // Same developer pasting the same answer across tasks
            $repeats = TaskSubmission::query()->where('developer_id', $developer->id)
                ->where('answer_hash', $submission->answer_hash)->count();
            if ($repeats >= (int) $cfg['repeated_answer_threshold']) {
                $this->flag($developer, FraudFlagType::RepeatedAnswers, ['submission_id' => $submission->id, 'occurrences' => $repeats]);
                $flagged = true;
            }
        }

        if ($flagged) {
            $submission->forceFill(['is_flagged' => true])->save();
        }
    }

    public function inspectRejectionRate(User $developer): void
    {
        $cfg = config('platform.fraud');
        $approved = TaskSubmission::query()->where('developer_id', $developer->id)->where('status', SubmissionStatus::Approved->value)->count();
        $rejected = TaskSubmission::query()->where('developer_id', $developer->id)->where('status', SubmissionStatus::Rejected->value)->count();
        $reviewed = $approved + $rejected;

        if ($reviewed >= $cfg['rejection_rate_min_reviews'] && $rejected / $reviewed > $cfg['rejection_rate_threshold']) {
            $this->flag($developer, FraudFlagType::HighRejectionRate, ['approved' => $approved, 'rejected' => $rejected]);
        }
    }

    public function flag(User $user, FraudFlagType $type, array $details = []): ?FraudFlag
    {
        // One open flag per type per user; repeated signals don't pile up.
        $existing = FraudFlag::query()->where('user_id', $user->id)->where('type', $type->value)
            ->where('status', FraudFlagStatus::Open->value)->first();
        if ($existing) {
            return null;
        }

        $flag = FraudFlag::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'score' => $type->weight(),
            'details' => $details,
            'status' => FraudFlagStatus::Open,
        ]);
        $this->recalculate($user);

        AdminNotifier::send(new AdminAlertNotification(
            'Fraud signal: '.$type->label(), "{$user->name} ({$user->email}) — score {$user->fraud_score}",
            route('admin.users.show', $user), 'shield',
        ));

        return $flag;
    }

    public function review(FraudFlag $flag, User $admin, FraudFlagStatus $status): void
    {
        $flag->forceFill(['status' => $status, 'reviewed_by' => $admin->id, 'reviewed_at' => now()])->save();
        $this->recalculate($flag->user);
        app(ActivityLogger::class)->log('fraud_flag.'.$status->value, $flag, [], $admin);
    }

    public function recalculate(User $user): void
    {
        $score = FraudFlag::query()->where('user_id', $user->id)
            ->whereIn('status', [FraudFlagStatus::Open->value, FraudFlagStatus::Confirmed->value])->sum('score');
        $user->forceFill(['fraud_score' => min(100, (int) $score)])->save();
    }
}
