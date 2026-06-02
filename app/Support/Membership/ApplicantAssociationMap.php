<?php

namespace App\Support\Membership;

/**
 * Maps member applicant types to allowed association codes (see AssociationsSeeder).
 */
final class ApplicantAssociationMap
{
    /** @var list<string> */
    public const APPLICANT_TYPES = ['author', 'publisher', 'corporate_publisher', 'artist'];

    /** @var list<string> */
    public const AUTHOR_CODES = ['ANA', 'ANFAAN'];

    /** @var list<string> */
    public const PUBLISHER_CODES = ['NPA'];

    /** @var list<string> */
    public const ARTIST_CODES = ['SNA'];

    /**
     * @return list<string>
     */
    public static function allowedCodesFor(string $applicantType): array
    {
        return match ($applicantType) {
            'author' => self::AUTHOR_CODES,
            'publisher', 'corporate_publisher' => self::PUBLISHER_CODES,
            'artist' => self::ARTIST_CODES,
            default => [],
        };
    }

    public static function associationMatchesApplicantType(string $applicantType, ?string $associationCode): bool
    {
        if ($associationCode === null || trim($associationCode) === '') {
            return false;
        }

        $normalized = strtoupper(trim($associationCode));

        return in_array($normalized, self::allowedCodesFor($applicantType), true);
    }
}
