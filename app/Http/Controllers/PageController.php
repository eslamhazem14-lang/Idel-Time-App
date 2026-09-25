<?php

namespace App\Http\Controllers;

use App\Models\TaskCategory;
use App\Services\TaskPricingService;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(TaskPricingService $pricing): View
    {
        return view('public.home', [
            'example' => $pricing->quote('0.50'),
            'categories' => TaskCategory::query()->active()->limit(9)->get(),
        ]);
    }

    public function howItWorks(): View
    {
        return view('public.how-it-works');
    }

    public function developers(): View
    {
        return view('public.developers');
    }

    public function requesters(): View
    {
        return view('public.requesters');
    }

    public function pricing(TaskPricingService $pricing): View
    {
        return view('public.pricing', [
            'examples' => collect(['0.35', '0.50', '1.00', '2.50'])->map(fn ($r) => $pricing->quote($r)),
            'commission' => settings()->commissionPercent(),
        ]);
    }

    public function faq(): View
    {
        return view('public.faq', ['faqs' => self::faqs()]);
    }

    public static function faqs(): array
    {
        $min = settings()->money('min_withdrawal')->format();
        $commission = rtrim(rtrim(settings()->commissionPercent(), '0'), '.');
        $days = (int) settings('auto_approve_days');

        return [
            ['How do payments work?', "When a requester (or our moderation team) approves your submission, the task reward moves from your pending balance to your available balance. You can request a withdrawal once your available balance reaches {$min}. Withdrawals are reviewed and paid manually by bank transfer, PayPal or another agreed method."],
            ['How are tasks reviewed?', "Every submission is reviewed by the requester who posted the task, and administrators can review or override any decision. If a requester does not review your work within {$days} days, it is approved automatically — unless it was flagged by our quality checks."],
            ['What happens if a task is rejected?', 'You will see the rejection reason on the submission page. Rejected work is not paid and the slot is re-opened for other developers. If you believe the rejection is unfair, you can appeal within 14 days and an administrator will review it.'],
            ['What is the minimum withdrawal?', "The current minimum withdrawal is {$min}. The platform does not charge developers a withdrawal fee; your payment provider may."],
            ['Do I need to verify my account?', 'You need to verify your email address before you can start tasks, post tasks, or withdraw money. We may ask for additional verification for large withdrawals or if our fraud checks flag unusual activity.'],
            ['How many tasks are available?', 'Availability depends entirely on what requesters post. Some days there will be many short tasks, other days very few. Earnings depend on task availability, qualification and approval — we do not guarantee any income.'],
            ['What does the platform charge?', "Requesters pay the developer reward plus a {$commission}% platform commission. Developers keep 100% of the listed reward. Example: a \$0.50 task costs the requester \$0.65."],
            ['How long do I have to finish a task?', 'When you start a task, a timer begins. You get roughly twice the estimated time (minimum '.settings('claim_min_minutes').' minutes), plus a short grace period. If time runs out, the task goes back into the pool.'],
            ['Can I use AI tools to complete tasks?', 'Many tasks exist precisely because a human needs to verify something. Follow each task’s instructions; submissions that are clearly unverified copy-paste are rejected and can lead to account review.'],
        ];
    }
}
