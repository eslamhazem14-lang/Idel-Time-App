<?php

namespace App\Support;

use App\Models\Task;

/**
 * Automated hints shown to admins while reviewing a task ("potential issues").
 */
final class TaskReviewChecklist
{
    private const RISKY_WORDS = ['password', 'login', 'credential', 'captcha', 'seed phrase', 'private key', 'bank account', 'ssn', 'credit card', 'download and run', 'install this', 'crypto wallet', 'follow us', 'like our', 'review our app'];

    /** @return array<int, array{level: string, message: string}> */
    public static function issues(Task $task): array
    {
        $issues = [];
        $text = mb_strtolower($task->title.' '.$task->description.' '.$task->instructions.' '.json_encode($task->payload));

        foreach (self::RISKY_WORDS as $word) {
            if (str_contains($text, $word)) {
                $issues[] = ['level' => 'danger', 'message' => "Mentions “{$word}” — check for credential harvesting, malware or fake-review requests."];
            }
        }

        $perMinute = intdiv($task->reward->cents, max(1, $task->estimated_minutes));
        if ($perMinute < 3) {
            $issues[] = ['level' => 'warning', 'message' => 'Very low reward per minute ('.$task->rewardPerMinute().'). Developers may skip it.'];
        }
        if (mb_strlen($task->instructions) < 60) {
            $issues[] = ['level' => 'warning', 'message' => 'Instructions are short — make sure the expected result is unambiguous.'];
        }
        if ($task->estimated_minutes > (int) settings('max_task_minutes')) {
            $issues[] = ['level' => 'warning', 'message' => 'Estimated time exceeds the current maximum task duration.'];
        }
        if (preg_match_all('/https?:\/\/[^\s"\\\\]+/', $text, $m) && count(array_unique($m[0])) > 3) {
            $issues[] = ['level' => 'warning', 'message' => 'Contains many external links ('.count(array_unique($m[0])).').'];
        }
        if ($task->requester?->fraud_score > 0) {
            $issues[] = ['level' => 'danger', 'message' => "Requester has a fraud score of {$task->requester->fraud_score}."];
        }
        if ($task->available_slots > 500) {
            $issues[] = ['level' => 'info', 'message' => "Large task: {$task->available_slots} slots."];
        }

        return $issues;
    }
}
