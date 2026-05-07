<?php

namespace App\Notifications\System;

class InstitutionDeclarationSubmittedAdminSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected int $declarationId,
        protected string $institutionName,
        protected string $licensingYear,
    ) {}

    public function toArray(object $notifiable): array
    {
        $message = sprintf(
            '%s submitted an annual declaration for licensing year %s. Review it in the admin declarations queue.',
            $this->institutionName,
            $this->licensingYear
        );

        return [
            ...$this->basePayload(
                'institution_declaration_submitted',
                'Institution declaration submitted',
                $message,
                'info',
                '/admin/declarations',
                [
                    'entity_type' => 'declaration',
                    'entity_id' => $this->declarationId,
                    'licensing_year' => $this->licensingYear,
                    'institution_name' => $this->institutionName,
                ]
            ),
            'category' => 'licensing',
        ];
    }
}
