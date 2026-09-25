<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isDeveloper();
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'decimal:0,2', 'min:0.01', 'max:1000000'],
            'method' => ['required', Rule::in((array) settings('withdrawal_methods'))],
            'account_details' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    public function amount(): Money
    {
        return Money::of((string) $this->validated('amount'));
    }

    public function paymentMethod(): PaymentMethod
    {
        return PaymentMethod::from($this->validated('method'));
    }
}
