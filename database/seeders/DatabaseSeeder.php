<?php

namespace Database\Seeders;

use App\Enums\AdViewStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Report;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskClaim;
use App\Models\TaskSubmission;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Services\AdRewardService;
use App\Services\DepositService;
use App\Services\DisputeService;
use App\Services\SubmissionReviewService;
use App\Services\TaskClaimService;
use App\Services\TaskService;
use App\Services\TaskSubmissionService;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Demo environment. Everything money-related goes through the real services,
 * so balances always reconcile with the ledger (php artisan wallet:reconcile).
 *
 * Credentials:
 *   admin     SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD (random password printed if unset)
 *   demo      dev1..dev10@idletime.test, acme|globex|initech@idletime.test  / SEED_DEMO_PASSWORD (default "password")
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() && ! $this->command?->confirm('This seeds DEMO data. Continue in production?', false)) {
            return;
        }

        config(['mail.default' => 'array', 'queue.default' => 'sync']);
        mt_srand(20260925);
        $start = now()->copy();
        Carbon::setTestNow($start->copy()->subDays(21)->setTime(9, 0));

        $categories = $this->categories();
        $admin = $this->admin();
        [$developers, $requesters] = $this->demoUsers();
        $this->templates($categories);

        // Requesters fund their accounts (manual deposit confirmed by an admin)
        $deposits = app(DepositService::class);
        foreach ($requesters as $requester) {
            [$deposit] = $deposits->request($requester, Money::of('600.00'), PaymentMethod::BankTransfer, 'DEMO-'.Str::upper(Str::random(6)));
            $deposits->approve($deposit, $admin, 'Demo funds');
        }

        $tasks = $this->tasks($requesters, $categories, $admin, $start);
        $this->simulateWork($developers, $tasks, $start);
        $this->extras($developers, $requesters, $admin, $tasks);
        $this->adViews($developers, $admin);

        Carbon::setTestNow();
        $this->command?->info('Demo data seeded. Wallet reconciliation: '.(app(WalletService::class)->reconcile() === [] ? 'OK' : 'MISMATCH'));
    }

    private function categories(): array
    {
        $this->call(CategorySeeder::class);

        return TaskCategory::query()->get()->keyBy('slug')->all();
    }

    private function admin(): User
    {
        $email = env('SEED_ADMIN_EMAIL', 'admin@idletime.test');
        $password = env('SEED_ADMIN_PASSWORD');
        if (! $password) {
            $password = Str::password(20);
            $this->command?->warn("SEED_ADMIN_PASSWORD not set. Generated admin password for {$email}: {$password}");
        }

        $admin = User::query()->firstOrNew(['email' => $email]);
        $admin->fill(['name' => 'Platform Admin', 'password' => $password, 'timezone' => 'UTC']);
        $admin->role = UserRole::Admin;
        $admin->status = UserStatus::Active;
        $admin->email_verified_at = now();
        $admin->save();
        app(WalletService::class)->walletFor($admin);

        return $admin;
    }

    private function demoUsers(): array
    {
        $password = env('SEED_DEMO_PASSWORD', 'password');
        $skills = [['php', 'sql'], ['javascript', 'css'], ['python', 'data'], ['go', 'security'], ['qa', 'accessibility'], ['php', 'testing'], ['javascript', 'node'], ['git', 'docs'], ['security', 'http'], ['python', 'regex']];
        $names = ['Demo Dev Ada', 'Demo Dev Linus', 'Demo Dev Grace', 'Demo Dev Ken', 'Demo Dev Margaret', 'Demo Dev Dennis', 'Demo Dev Barbara', 'Demo Dev Guido', 'Demo Dev Radia', 'Demo Dev Speedy'];
        $countries = ['US', 'DE', 'IN', 'BR', 'NG', 'PL', 'CA', 'NL', 'KE', 'PH'];

        $developers = [];
        foreach ($names as $i => $name) {
            $u = $this->user('dev'.($i + 1).'@idletime.test', $name, UserRole::Developer, $password);
            $u->forceFill(['skills' => $skills[$i], 'country' => $countries[$i], 'bio' => 'Fake demo developer account.', 'registration_ip' => '198.51.100.'.($i + 10)])->save();
            $u->developerProfile()->updateOrCreate([], ['skills' => $skills[$i], 'experience_years' => 2 + $i, 'github_url' => 'https://github.com/example-dev-'.($i + 1), 'languages' => ['English']]);
            $developers[] = $u;
        }

        $requesters = [];
        foreach ([['acme', 'Acme Labs (demo)'], ['globex', 'Globex AI (demo)'], ['initech', 'Initech QA (demo)']] as [$slug, $name]) {
            $requesters[] = $this->user("{$slug}@idletime.test", $name, UserRole::Requester, $password);
        }

        return [$developers, $requesters];
    }

    private function user(string $email, string $name, UserRole $role, string $password): User
    {
        $u = User::query()->firstOrNew(['email' => $email]);
        $u->fill(['name' => $name, 'password' => $password, 'timezone' => 'UTC']);
        $u->role = $role;
        $u->status = UserStatus::Active;
        $u->email_verified_at = now();
        $u->save();
        if ($role === UserRole::Developer) {
            $u->developerProfile()->firstOrCreate([]);
        }
        app(WalletService::class)->walletFor($u);

        return $u;
    }

    private function templates(array $categories): void
    {
        $rows = [
            ['AI answer review', 'ai-evaluation', 'ai_evaluation', 'Rate an AI answer', 'Evaluate an AI-generated answer.', "Read the prompt and response.\nRate correctness, relevance and quality from 1–5.\nExplain any score of 2 or lower.", 3, '0.35'],
            ['Code snippet review', 'code-review', 'code_review', 'Review a code snippet', 'Targeted review of a short snippet.', "Read the code.\nAnswer each question.\nList issues with line numbers.", 5, '0.50'],
            ['Website smoke test', 'website-testing', 'website_qa', 'Test a web page', 'Walk through a page and report issues.', "Open the URL.\nFollow the steps.\nReport environment and findings; attach screenshots of issues.", 7, '0.75'],
            ['Docs fact check', 'documentation', 'documentation_verification', 'Verify documentation claims', 'Check claims against documentation.', "Open the source.\nMark each claim accurate, inaccurate or unverifiable.\nExplain inaccuracies.", 4, '0.40'],
            ['Bug reproduction', 'bug-reproduction', 'bug_reproduction', 'Reproduce a reported bug', 'Follow reproduction steps and report.', "Use a clean environment.\nFollow the steps exactly.\nReport outcome and your environment.", 8, '0.90'],
        ];
        foreach ($rows as [$name, $cat, $type, $title, $desc, $instr, $min, $reward]) {
            TaskTemplate::query()->updateOrCreate(['name' => $name], [
                'category_id' => $categories[$cat]->id, 'type' => $type, 'title' => $title, 'description' => $desc,
                'instructions' => $instr, 'estimated_minutes' => $min, 'suggested_reward' => $reward, 'difficulty' => 'easy', 'is_active' => true,
            ]);
        }
    }

    /** @return Task[] */
    private function tasks(array $requesters, array $categories, User $admin, Carbon $end): array
    {
        $taskService = app(TaskService::class);
        $library = DemoContent::tasks();
        $tasks = [];

        for ($i = 0; $i < 50; $i++) {
            [$cat, $type, $title, $desc, $instructions, $minutes, $reward, $difficulty, $skills, $payload] = $library[$i % count($library)];
            Carbon::setTestNow(Carbon::now()->addMinutes(mt_rand(20, 300)));
            $requester = $requesters[$i % count($requesters)];

            $task = $taskService->create($requester, [
                'category_id' => $categories[$cat]->id,
                'type' => $type,
                'title' => $i >= count($library) ? $title.' (set B)' : $title,
                'description' => $desc,
                'instructions' => $instructions,
                'estimated_minutes' => $minutes,
                'reward' => $reward,
                'slots' => [3, 5, 8, 10, 15, 20][$i % 6],
                'difficulty' => $difficulty,
                'required_skills' => $skills,
                'payload' => $payload,
                'deadline' => $end->copy()->addDays(30 + $i),
            ]);

            match (true) {
                $i >= 47 => null, // leave pending approval for the admin demo
                $i === 45 || $i === 46 => $taskService->reject($task, $admin, 'Instructions are too vague for a microtask. Please list the exact steps and expected output.'),
                default => $taskService->approve($task, $admin),
            };
            $tasks[] = $task->refresh();
        }

        $taskService->pause($tasks[44]);

        return $tasks;
    }

    private function simulateWork(array $developers, array $tasks, Carbon $end): void
    {
        $claims = app(TaskClaimService::class);
        $submissions = app(TaskSubmissionService::class);
        $reviews = app(SubmissionReviewService::class);
        $pendingReview = [];
        $seed = 100;

        for ($day = 14; $day >= 0; $day--) {
            $dayStart = $end->copy()->subDays($day)->setTime(8, 0);
            Carbon::setTestNow($dayStart);

            // Requesters review yesterday's submissions
            foreach ($pendingReview as $k => $submission) {
                if ($day === 0 && $k % 2 === 0) {
                    continue; // leave some pending for the review-queue demo
                }
                Carbon::setTestNow(Carbon::now()->addMinutes(mt_rand(5, 40)));
                $roll = mt_rand(1, 100);
                $reviewer = $submission->task->requester;
                if ($roll <= 82) {
                    $reviews->approve($submission, $reviewer);
                } elseif ($roll <= 94) {
                    $reviews->reject($submission, $reviewer, 'The answer does not address the questions asked in the instructions.');
                }
                unset($pendingReview[$k]);
            }

            $perDay = $day === 0 ? 4 : mt_rand(6, 10);
            for ($n = 0; $n < $perDay; $n++) {
                // dev1 (the main demo account) works often but keeps plenty of tasks available to try
                $developer = mt_rand(1, 100) <= 20 && $developers[0]->claims()->count() < 14
                    ? $developers[0]
                    : $developers[mt_rand(1, count($developers) - 1)];
                $task = collect($tasks)->shuffle()->first(fn (Task $t) => $t->refresh()->isClaimable()
                    && ! TaskClaim::query()->where('task_id', $t->id)->where('developer_id', $developer->id)->exists());
                if (! $task) {
                    continue;
                }

                Carbon::setTestNow(Carbon::now()->addMinutes(mt_rand(10, 60)));
                $claim = $claims->claim($task, $developer, '198.51.100.'.(10 + array_search($developer, $developers, true)));

                $speedy = $developer->email === 'dev10@idletime.test';
                $seconds = $speedy ? mt_rand(15, 30) : (int) ($task->estimated_minutes * 60 * mt_rand(55, 130) / 100);
                Carbon::setTestNow(Carbon::now()->addSeconds($seconds));

                if (mt_rand(1, 100) <= 6) {
                    $claims->release($claim);

                    continue;
                }
                $answer = DemoContent::answerFor($task->type, $task->payload, $speedy ? 1 : ++$seed);
                $pendingReview[] = $submissions->submit($claim, $answer, [], '198.51.100.'.(10 + array_search($developer, $developers, true)));
            }
        }
        Carbon::setTestNow($end);
    }

    private function extras(array $developers, array $requesters, User $admin, array $tasks): void
    {
        $wallets = app(WalletService::class);

        // Appeal on one rejected submission
        $rejected = TaskSubmission::query()->where('status', 'rejected')->with('task')->first();
        if ($rejected) {
            app(DisputeService::class)->open($rejected, $rejected->developer, 'I answered every question in the instructions; please take another look at the second answer, it covers the edge case.');
        }

        // Withdrawals: give two developers enough balance for the demo, one paid, one pending
        $withdrawals = app(WithdrawalService::class);
        foreach ([1 => true, 2 => false] as $index => $paid) {
            $dev = $developers[$index];
            $wallets->adjust($dev, Money::of('15.00'), 'Demo launch bonus', $admin);
            $w = $withdrawals->request($dev, Money::of('12.00'), $paid ? PaymentMethod::Paypal : PaymentMethod::BankTransfer, $paid ? 'demo-payout@example.test' : "Demo Account Holder\nDemo Bank\nIBAN DE00 0000 0000 0000 0000 00");
            if ($paid) {
                $withdrawals->markPaid($w, $admin, 'Paid via PayPal (demo)', 'PAYPAL-DEMO-001');
            }
        }

        // A pending deposit for the admin queue
        app(DepositService::class)->request($requesters[1], Money::of('250.00'), PaymentMethod::Paypal, 'PAYPAL-DEMO-TX-42');

        Report::query()->create([
            'reporter_id' => $developers[3]->id,
            'reported_user_id' => $tasks[10]->requester_id,
            'task_id' => $tasks[10]->id,
            'reason' => ReportReason::UnderEstimated,
            'description' => 'Took me about twice the estimated time on mobile.',
            'status' => ReportStatus::Open,
        ]);
    }

    /** Watched ads: last month's were paid out from a (fictional) ad network payment, this month's await payout. */
    private function adViews(array $developers, User $admin): void
    {
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();
        foreach (array_slice($developers, 0, 6) as $i => $developer) {
            foreach ([[$lastMonth, 12 - $i], [now()->startOfMonth(), 6 - $i]] as [$month, $count]) {
                for ($n = 0; $n < $count; $n++) {
                    $at = $month->copy()->addDays(mt_rand(0, max(0, min(27, (int) $month->diffInDays(now()) - 1))))->addMinutes(mt_rand(0, 1400));
                    $developer->adViews()->create([
                        'uuid' => (string) Str::uuid(), 'provider' => 'demo', 'status' => AdViewStatus::Counted,
                        'started_at' => $at->copy()->subSeconds(30), 'completed_at' => $at,
                    ]);
                }
            }
        }

        app(AdRewardService::class)->distribute(
            $lastMonth, $lastMonth->copy()->endOfMonth(), Money::of('1.20'), $admin, 'DEMO-PAYMENT', 'Demo data: not a real payment.',
        );
    }
}
