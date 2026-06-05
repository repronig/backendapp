<?php

namespace App\Support\Membership;

/**
 * Allowed member_author_category values per applicant_type (onboarding / mandate).
 */
final class MemberApplicationCategoryMap
{
    /** @var list<string> */
    public const AUTHOR = ['author', 'journalist', 'photographer', 'other'];

    /** @var list<string> */
    public const ARTIST = ['illustrator', 'carver', 'painter', 'sculptor', 'other'];

    /** @var list<string> */
    public const PUBLISHER = [
        'book_publisher',
        'magazine_publisher',
        'newspaper_publisher',
        'digital_web_publisher',
        'other',
    ];

    /**
     * @return list<string>
     */
    public static function allowedFor(?string $applicantType): array
    {
        return match ($applicantType) {
            'artist' => self::ARTIST,
            'publisher', 'corporate_publisher' => self::PUBLISHER,
            'author' => self::AUTHOR,
            default => [],
        };
    }

    public static function isIndividualMemberType(?string $memberAuthorType): bool
    {
        return $memberAuthorType === 'individual';
    }

    public static function isOrgMemberType(?string $memberAuthorType): bool
    {
        return in_array($memberAuthorType, ['corporate', 'agent'], true);
    }
}
