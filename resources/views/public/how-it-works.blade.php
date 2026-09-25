<x-layouts.public title="How it works" description="Start your AI task, pick a microtask sized to the wait, submit, and get paid after approval.">
    <section class="mx-auto max-w-6xl px-4 pt-16 sm:px-6">
        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">How it works</h1>
        <p class="mt-3 max-w-2xl text-muted">Built around the few minutes you spend waiting on an AI coding agent.</p>
        <div class="mt-10">@include('public._steps')</div>
    </section>
    <section class="mx-auto mt-16 grid max-w-6xl gap-6 px-4 sm:px-6 lg:grid-cols-3">
        <div class="card card-pad">
            <h2 class="flex items-center gap-2 font-semibold"><x-icon name="clock" class="text-primary" /> A fair, server-side timer</h2>
            <p class="mt-2 text-sm text-muted">When you claim a task, it is locked for you and a countdown starts — roughly twice the estimated time plus a short grace period. The server keeps the time, so a refreshed tab or a sleeping laptop doesn't change it. If time runs out, the slot goes back to the pool.</p>
        </div>
        <div class="card card-pad">
            <h2 class="flex items-center gap-2 font-semibold"><x-icon name="eye" class="text-primary" /> Review & approval</h2>
            <p class="mt-2 text-sm text-muted">Requesters review each submission. They can approve, reject with a reason, or ask for a revision. Unreviewed work is approved automatically after {{ (int) settings('auto_approve_days') }} days so you are never left waiting indefinitely.</p>
        </div>
        <div class="card card-pad">
            <h2 class="flex items-center gap-2 font-semibold"><x-icon name="wallet" class="text-primary" /> Pending → available → withdrawn</h2>
            <p class="mt-2 text-sm text-muted">Submitted rewards show as pending. Approval moves them to your available balance. From {{ settings()->money('min_withdrawal')->format() }} you can request a withdrawal by bank transfer, PayPal or another manual method.</p>
        </div>
    </section>
    <section class="mx-auto mt-16 max-w-6xl px-4 sm:px-6">
        <div class="card flex flex-col items-start justify-between gap-4 p-6 sm:flex-row sm:items-center">
            <div>
                <h2 class="font-semibold">Rejected? You can appeal.</h2>
                <p class="mt-1 text-sm text-muted">You always see the rejection reason. If you disagree, open an appeal within 14 days and an administrator reviews it.</p>
            </div>
            <a href="{{ route('register') }}" class="btn-primary">Start Earning</a>
        </div>
    </section>
</x-layouts.public>
