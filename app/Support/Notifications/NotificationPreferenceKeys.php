<?php

namespace App\Support\Notifications;

final class NotificationPreferenceKeys
{
    public const ACCOUNT_SECURITY = 'account_security';

    public const APPLICATION_UPDATES = 'application_updates';

    public const WORK_REVIEWS = 'work_reviews';

    public const LICENSING_UPDATES = 'licensing_updates';

    public const PAYMENT_UPDATES = 'payment_updates';

    public const APPROVAL_UPDATES = 'approval_updates';

    public const GENERAL_ANNOUNCEMENTS = 'general_announcements';

    public const DOCUMENT_UPDATES = 'document_updates';

    public const TAXONOMY = [
        self::ACCOUNT_SECURITY,
        self::APPLICATION_UPDATES,
        self::WORK_REVIEWS,
        self::LICENSING_UPDATES,
        self::PAYMENT_UPDATES,
        self::APPROVAL_UPDATES,
        self::GENERAL_ANNOUNCEMENTS,
        self::DOCUMENT_UPDATES,
    ];

    private const EVENT_TO_TAXONOMY = [
        'password_reset' => self::ACCOUNT_SECURITY,
        'email_verification' => self::ACCOUNT_SECURITY,
        'security_event' => self::ACCOUNT_SECURITY,

        'member_application_changes_requested' => self::APPLICATION_UPDATES,
        'member_application_rejected' => self::APPLICATION_UPDATES,
        'member_application_approved' => self::APPLICATION_UPDATES,
        'member_application_submitted_association' => self::APPLICATION_UPDATES,

        'work_update_requested' => self::WORK_REVIEWS,
        'work_update_request_reviewed' => self::WORK_REVIEWS,
        'work_submitted_admin' => self::WORK_REVIEWS,
        'work_reviewed_member' => self::WORK_REVIEWS,

        'invoice_generated' => self::LICENSING_UPDATES,
        'invoice_due_reminder' => self::LICENSING_UPDATES,
        'invoice_overdue_reminder' => self::LICENSING_UPDATES,
        'declaration_approved' => self::LICENSING_UPDATES,
        'declaration_rejected' => self::LICENSING_UPDATES,
        'institution_declaration_submitted' => self::LICENSING_UPDATES,
        'institution_welcome' => self::LICENSING_UPDATES,
        'member_welcome' => self::APPLICATION_UPDATES,

        'payment_initiated' => self::PAYMENT_UPDATES,
        'payment_received' => self::PAYMENT_UPDATES,
        'payment_received_admin' => self::PAYMENT_UPDATES,
        'offline_licence_payment_rejected' => self::PAYMENT_UPDATES,
        'offline_invoice_payment_submitted_admin' => self::PAYMENT_UPDATES,

        'institution_approved' => self::APPROVAL_UPDATES,
        'institution_rejected' => self::APPROVAL_UPDATES,
        'association_enabled' => self::APPROVAL_UPDATES,
        'association_disabled' => self::APPROVAL_UPDATES,

        'admin_member_approved' => self::DOCUMENT_UPDATES,
    ];

    public static function normalize(string $key): string
    {
        return self::EVENT_TO_TAXONOMY[$key] ?? $key;
    }

    private function __construct() {}
}
