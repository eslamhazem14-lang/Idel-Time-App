<?php

namespace App\Support;

/**
 * Canonical form of an email address used to detect duplicate accounts
 * (e.g. "Jane.Doe+work@gmail.com" and "janedoe@gmail.com").
 */
final class EmailNormalizer
{
    private const DOTLESS_DOMAINS = ['gmail.com', 'googlemail.com'];

    public static function normalize(string $email): string
    {
        $email = mb_strtolower(trim($email));
        if (! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $local = explode('+', $local, 2)[0];

        if (in_array($domain, self::DOTLESS_DOMAINS, true)) {
            $local = str_replace('.', '', $local);
            $domain = 'gmail.com';
        }

        return $local.'@'.$domain;
    }
}
