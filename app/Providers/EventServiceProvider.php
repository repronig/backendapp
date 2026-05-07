<?php

namespace App\Providers;

use App\Events\AssociationDisabled;
use App\Events\InstitutionAnnualDeclarationApproved;
use App\Events\InstitutionApproved;
use App\Events\InstitutionInvoiceGenerated;
use App\Events\LicencePaymentReceived;
use App\Events\MemberApplicationApprovedByAssociation;
use App\Listeners\NotifyAdminsOfMemberApproval;
use App\Listeners\SendAssociationDisabledNotification;
use App\Listeners\SendDeclarationApprovedNotification;
use App\Listeners\SendInstitutionApprovedEmail;
use App\Listeners\SendInvoiceGeneratedEmail;
use App\Listeners\SendMemberApprovalConfirmation;
use App\Listeners\SendPaymentReceiptEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MemberApplicationApprovedByAssociation::class => [
            NotifyAdminsOfMemberApproval::class,
            SendMemberApprovalConfirmation::class,
        ],
        InstitutionApproved::class => [
            SendInstitutionApprovedEmail::class,
        ],
        InstitutionAnnualDeclarationApproved::class => [
            SendDeclarationApprovedNotification::class,
        ],
        InstitutionInvoiceGenerated::class => [
            SendInvoiceGeneratedEmail::class,
        ],
        LicencePaymentReceived::class => [
            SendPaymentReceiptEmail::class,
        ],
        AssociationDisabled::class => [
            SendAssociationDisabledNotification::class,
        ],
    ];
}
