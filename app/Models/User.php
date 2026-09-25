<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Notifications\VerifyEmailNotification;
use App\Support\EmailNormalizer;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Only profile fields are mass assignable. Role, status and fraud fields
     * are always set explicitly by trusted code.
     */
    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'bio', 'country', 'timezone', 'skills',
    ];

    protected $hidden = ['password', 'remember_token', 'registration_ip', 'last_login_ip'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_active_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'skills' => 'array',
            'developer_level' => 'integer',
            'fraud_score' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('email')) {
                $user->email_normalized = EmailNormalizer::normalize($user->email);
            }
        });
    }

    public function isDeveloper(): bool
    {
        return $this->role === UserRole::Developer;
    }

    public function isRequester(): bool
    {
        return $this->role === UserRole::Requester;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    public function isFlagged(): bool
    {
        return $this->fraud_score >= (int) config('platform.fraud.review_score');
    }

    public function homeRoute(): string
    {
        return $this->role->homeRoute();
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->filter()->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }

    public function developerProfile(): HasOne
    {
        return $this->hasOne(DeveloperProfile::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(TaskClaim::class, 'developer_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(TaskSubmission::class, 'developer_id');
    }

    public function requestedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'requester_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'developer_id');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class, 'requester_id');
    }

    public function fraudFlags(): HasMany
    {
        return $this->hasMany(FraudFlag::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
