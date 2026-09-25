<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private readonly WalletService $wallets,
        private readonly FraudDetectionService $fraud,
        private readonly ActivityLogger $logger,
    ) {}

    public function register(array $data, ?string $ip = null): User
    {
        $role = UserRole::from($data['role']);
        if (! in_array($role, UserRole::registrable(), true)) {
            throw BusinessRuleException::make('Invalid account type.');
        }

        $user = DB::transaction(function () use ($data, $role, $ip) {
            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'timezone' => $data['timezone'] ?? 'UTC',
            ]);
            $user->role = $role;
            $user->status = UserStatus::Active;
            $user->registration_ip = $ip;
            $user->last_login_ip = $ip;
            $user->save();

            if ($role === UserRole::Developer) {
                $user->developerProfile()->create([]);
            }
            $this->wallets->walletFor($user);

            return $user;
        });

        $this->logger->log('user.registered', $user, ['role' => $role->value], $user);
        $this->fraud->inspectRegistration($user, $ip);

        if (settings('require_email_verification')) {
            event(new Registered($user));
        } else {
            $user->markEmailAsVerified();
        }
        $user->notify(new WelcomeNotification);

        return $user;
    }

    public function recordLogin(User $user, ?string $ip): void
    {
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $ip, 'last_active_at' => now()])->save();
        $this->logger->log('user.login', $user, [], $user);
        $this->fraud->inspectIp($user, $ip);
    }

    public function suspend(User $user, User $admin, string $reason): void
    {
        if ($user->isAdmin()) {
            throw BusinessRuleException::make('Administrators cannot be suspended.');
        }
        $user->forceFill(['status' => UserStatus::Suspended, 'suspended_at' => now(), 'suspension_reason' => $reason])->save();
        $user->tokens()->delete();
        $this->logger->log('user.suspended', $user, ['reason' => $reason], $admin);
    }

    public function reactivate(User $user, User $admin): void
    {
        $user->forceFill(['status' => UserStatus::Active, 'suspended_at' => null, 'suspension_reason' => null])->save();
        $this->logger->log('user.reactivated', $user, [], $admin);
    }
}
