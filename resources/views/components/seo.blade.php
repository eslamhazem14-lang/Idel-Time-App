@props(['title' => null, 'description' => null, 'noindex' => false])
@php
    $site = config('app.name');
    $fullTitle = $title ? "{$title} · {$site}" : "{$site} — Turn AI Waiting Time Into Money";
    $description = $description ?? 'Complete small, paid technical microtasks while your AI coding agent works. Code review, AI evaluation, website QA and more — 2 to 15 minutes each.';
@endphp
<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<link rel="canonical" href="{{ url()->current() }}">
@if ($noindex)<meta name="robots" content="noindex, nofollow">@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $site }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ asset('og-image.png') }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $description }}">
