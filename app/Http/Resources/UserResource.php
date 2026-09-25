<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'email_verified' => $this->hasVerifiedEmail(),
            'timezone' => $this->timezone,
            'country' => $this->country,
            'skills' => $this->skills ?? [],
            'developer_level' => $this->when($this->isDeveloper(), $this->developer_level),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
