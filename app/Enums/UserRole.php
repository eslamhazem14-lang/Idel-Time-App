<?php

namespace App\Enums;

enum UserRole: string
{
    case Developer = 'developer';
    case Requester = 'requester';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Developer => 'Developer',
            self::Requester => 'Task Requester',
            self::Admin => 'Administrator',
        };
    }

    public function homeRoute(): string
    {
        return match ($this) {
            self::Developer => 'developer.dashboard',
            self::Requester => 'requester.dashboard',
            self::Admin => 'admin.dashboard',
        };
    }

    /** Roles a visitor may pick at registration. */
    public static function registrable(): array
    {
        return [self::Developer, self::Requester];
    }
}
