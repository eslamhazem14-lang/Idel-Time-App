<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdPayout extends Model
{
    protected $fillable = [
        'period_start', 'period_end', 'gross_amount', 'share_percent', 'developer_pool', 'distributed_amount',
        'view_count', 'developer_count', 'reference', 'note', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'gross_amount' => MoneyCast::class,
            'developer_pool' => MoneyCast::class,
            'distributed_amount' => MoneyCast::class,
        ];
    }

    public function views(): HasMany
    {
        return $this->hasMany(AdView::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
