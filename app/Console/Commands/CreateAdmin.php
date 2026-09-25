<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'platform:create-admin {--name=} {--email=} {--password=}';

    protected $description = 'Create (or promote) an administrator account';

    public function handle(WalletService $wallets): int
    {
        $name = $this->option('name') ?: $this->ask('Name', 'Administrator');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Password (min 12 characters)');

        $validator = Validator::make(compact('name', 'email', 'password'), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:12',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->fill(['name' => $name, 'password' => $password]);
        $user->role = UserRole::Admin;
        $user->status = UserStatus::Active;
        $user->email_verified_at ??= now();
        $user->save();
        $wallets->walletFor($user);

        $this->info("Administrator {$email} is ready.");

        return self::SUCCESS;
    }
}
