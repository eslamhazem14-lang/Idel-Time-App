<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect(['home' => '1.0', 'how-it-works' => '0.8', 'developers' => '0.8', 'requesters' => '0.8', 'pricing' => '0.7', 'faq' => '0.6', 'login' => '0.3', 'register' => '0.5'])
            ->map(fn ($priority, $route) => ['loc' => route($route), 'priority' => $priority]);

        return response()->view('seo.sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /requester', 'Disallow: /developer', 'Disallow: /work', 'Disallow: /wallet', 'Disallow: /api', 'Allow: /', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
