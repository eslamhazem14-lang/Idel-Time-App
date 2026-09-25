<?php

namespace App\Notifications;

/** Generic admin alert: new task, withdrawal, deposit, fraud flag, dispute. */
class AdminAlertNotification extends PlatformNotification
{
    public function __construct(
        private readonly string $alertTitle,
        private readonly string $alertBody,
        private readonly ?string $alertUrl = null,
        private readonly string $alertIcon = 'bell',
    ) {}

    protected function title(): string
    {
        return $this->alertTitle;
    }

    protected function body(): string
    {
        return $this->alertBody;
    }

    protected function url(): ?string
    {
        return $this->alertUrl;
    }

    protected function icon(): string
    {
        return $this->alertIcon;
    }
}
