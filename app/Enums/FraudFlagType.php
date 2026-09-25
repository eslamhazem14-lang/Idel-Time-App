<?php

namespace App\Enums;

enum FraudFlagType: string
{
    case DuplicateSubmission = 'duplicate_submission';
    case RepeatedAnswers = 'repeated_answers';
    case SimilarEmail = 'similar_email';
    case FastCompletion = 'fast_completion';
    case HighRejectionRate = 'high_rejection_rate';
    case SharedIp = 'shared_ip';

    public function label(): string
    {
        return match ($this) {
            self::DuplicateSubmission => 'Duplicate submission on same task',
            self::RepeatedAnswers => 'Repeated identical answers',
            self::SimilarEmail => 'Possible duplicate account (email)',
            self::FastCompletion => 'Suspiciously fast completion',
            self::HighRejectionRate => 'Excessive rejection rate',
            self::SharedIp => 'Many accounts from one IP',
        };
    }

    /** Points this signal adds to a user's fraud score (0–100). */
    public function weight(): int
    {
        return match ($this) {
            self::DuplicateSubmission => 30,
            self::RepeatedAnswers => 20,
            self::SimilarEmail => 25,
            self::FastCompletion => 10,
            self::HighRejectionRate => 20,
            self::SharedIp => 15,
        };
    }
}
