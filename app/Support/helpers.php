<?php

use App\Services\SettingsService;
use App\Support\Money;

if (! function_exists('settings')) {
    /**
     * Read a runtime platform setting (admin-editable, falls back to config/platform.php).
     */
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $service = app(SettingsService::class);

        return $key === null ? $service : $service->get($key, $default);
    }
}

if (! function_exists('money')) {
    function money(Money|string|int|null $value): string
    {
        return $value === null ? '—' : Money::of($value)->format();
    }
}
