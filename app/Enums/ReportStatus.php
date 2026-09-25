<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Open = 'open';
    case Reviewing = 'reviewing';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'amber',
            self::Reviewing => 'violet',
            self::Resolved => 'green',
            self::Dismissed => 'gray',
        };
    }
}
