<?php

namespace App\Listeners;

use App\Actions\Licensing\GenerateInstitutionInvoiceAction;
use App\Events\InstitutionAnnualDeclarationApproved;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateInvoiceAfterDeclarationApproval implements ShouldQueue
{
    use Queueable;

    public function __construct(protected GenerateInstitutionInvoiceAction $generateInstitutionInvoiceAction) {}

    public function handle(InstitutionAnnualDeclarationApproved $event): void
    {
        $this->generateInstitutionInvoiceAction->execute($event->declaration, $event->approvedBy);
    }
}
