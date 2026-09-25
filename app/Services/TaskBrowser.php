<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Task discovery for developers (web + API share the same filters).
 */
class TaskBrowser
{
    public const SORTS = [
        'best_rate' => 'Best reward / minute',
        'highest_reward' => 'Highest reward',
        'shortest' => 'Shortest task',
        'newest' => 'Newest',
    ];

    public const TIME_BUCKETS = [
        '3' => 'Up to 3 min',
        '5' => 'Up to 5 min',
        '10' => 'Up to 10 min',
        '15' => 'Up to 15 min',
    ];

    public function query(User $developer, array $filters): Builder
    {
        $query = Task::query()->available()->notClaimedBy($developer)
            ->with('category')
            ->where('tasks.requester_id', '!=', $developer->id);

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
        }
        if (! empty($filters['max_minutes'])) {
            $query->where('estimated_minutes', '<=', (int) $filters['max_minutes']);
        }
        if (! empty($filters['min_reward'])) {
            $query->where('reward', '>=', Money::of((string) $filters['min_reward'])->toDecimal());
        }
        if (! empty($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['skill'])) {
            $query->whereJsonContains('required_skills', $filters['skill']);
        }
        if (! empty($filters['q'])) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $filters['q']).'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('description', 'like', $term));
        }

        return match ($filters['sort'] ?? 'best_rate') {
            'highest_reward' => $query->orderByDesc('reward')->orderBy('estimated_minutes'),
            'shortest' => $query->orderBy('estimated_minutes')->orderByDesc('reward'),
            'newest' => $query->orderByDesc('published_at')->orderByDesc('id'),
            default => $query->orderByRaw('(reward / estimated_minutes) DESC')->orderBy('estimated_minutes'),
        };
    }

    public function paginate(User $developer, array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->query($developer, $filters)->paginate($perPage)->withQueryString();
    }

    /** Tasks that fit into the given idle window, best reward/minute first. */
    public function recommended(User $developer, int $minutes, int $limit = 5)
    {
        return $this->query($developer, ['max_minutes' => max(1, $minutes), 'sort' => 'best_rate'])->limit($limit)->get();
    }

    public function skills(): array
    {
        return Task::query()->available()->pluck('required_skills')->flatten()->filter()->unique()->sort()->values()->all();
    }
}
