<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentMethod;
use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['account_details'];

    protected function casts(): array
    {
        return [
            'amount' => MoneyCast::class,
            'method' => PaymentMethod::class,
            'status' => WithdrawalStatus::class,
            'account_details' => 'encrypted',
            'processed_at' => 'datetime',
        ];
    }

    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
