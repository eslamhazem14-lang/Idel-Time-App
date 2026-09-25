<x-layouts.public title="FAQ" description="Answers about payments, reviews, rejections, withdrawals, verification, availability and commission.">
    <section class="mx-auto max-w-3xl px-4 pt-16 sm:px-6">
        <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Frequently asked questions</h1>
        <p class="mt-3 text-muted">Can't find what you're looking for? Sign in and report a problem from any task.</p>
        <div class="mt-10">@include('public._faq-list', ['faqs' => $faqs])</div>
    </section>
    @push('head')
        <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(fn ($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faqs)], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endpush
</x-layouts.public>
