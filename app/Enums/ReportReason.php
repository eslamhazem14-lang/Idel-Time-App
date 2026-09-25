<?php

namespace App\Enums;

enum ReportReason: string
{
    case UnclearInstructions = 'unclear_instructions';
    case BrokenContent = 'broken_content';
    case Inappropriate = 'inappropriate';
    case UnderEstimated = 'under_estimated';
    case Spam = 'spam';
    case UnfairReview = 'unfair_review';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::UnclearInstructions => 'Instructions are unclear',
            self::BrokenContent => 'Broken link or content',
            self::Inappropriate => 'Inappropriate or unsafe content',
            self::UnderEstimated => 'Takes much longer than estimated',
            self::Spam => 'Spam or scam',
            self::UnfairReview => 'Unfair review',
            self::Other => 'Other',
        };
    }
}
