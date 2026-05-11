<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesEnumValues;

enum SupportTicketCategory: string
{
    use ProvidesEnumValues;

    case TechnicalIssueOrError = 'technical_issue_or_error';
    case InformationRequired = 'information_required';
    case BillingOrLicensing = 'billing_or_licensing';
    case AccessOrAccount = 'access_or_account';
    case Other = 'other';
}
