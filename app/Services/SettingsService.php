<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Runtime platform configuration. Defaults come from config('platform.settings');
 * admins override them from the dashboard. Values are stored JSON-encoded.
 */
class SettingsService
{
    private const CACHE_KEY = 'platform.settings';

    private ?array $resolved = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function all(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $defaults = config('platform.settings', []);
        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                if (! Schema::hasTable('settings')) {
                    return [];
                }
            } catch (\Throwable) {
                return [];
            }

            return Setting::query()->pluck('value', 'key')
                ->map(fn ($v) => json_decode((string) $v, true))
                ->all();
        });

        return $this->resolved = array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    public function set(string $key, mixed $value): void
    {
        $this->setMany([$key => $value]);
    }

    public function setMany(array $values): void
    {
        $defaults = config('platform.settings', []);
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, $defaults)) {
                throw new InvalidArgumentException("Unknown setting [{$key}].");
            }
            Setting::query()->updateOrCreate(['key' => $key], ['value' => json_encode($value)]);
        }
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->resolved = null;
    }

    public function money(string $key): Money
    {
        return Money::of((string) $this->get($key));
    }

    public function commissionPercent(): string
    {
        return Money::of((string) $this->get('commission_percent'))->toDecimal();
    }
}
