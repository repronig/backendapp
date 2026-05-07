<?php

namespace App\Services\Integrations\WipoConnect;

use App\Models\Institution;
use App\Models\IntegrationOutboxEntry;
use App\Models\Licence;
use App\Models\Member;
use App\Models\Work;

/**
 * Builds outbound JSON for WIPO Connect. Extend operation-specific envelopes as WIPO publishes concrete schemas.
 */
class WipoConnectOutboundPayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(IntegrationOutboxEntry $entry): array
    {
        $entry->loadMissing('subject');

        if ($entry->subject instanceof Member) {
            $entry->subject->loadMissing('user');
        }

        return [
            'repronig' => [
                'outbox_id' => $entry->id,
                'operation' => $entry->operation,
                'provider' => $entry->provider->value,
            ],
            'payload' => is_array($entry->payload) ? $entry->payload : [],
            'subject' => $this->subjectPayload($entry),
            'wipo_connect' => $this->operationEnvelope($entry),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function operationEnvelope(IntegrationOutboxEntry $entry): array
    {
        return match ($entry->operation) {
            'sync_work' => $this->syncWorkEnvelope($entry),
            'sync_institution' => $this->syncInstitutionEnvelope($entry),
            'sync_member' => $this->syncMemberEnvelope($entry),
            'sync_licence' => $this->syncLicenceEnvelope($entry),
            default => [
                'schema_version' => 1,
                'operation' => $entry->operation,
                'note' => 'No dedicated WIPO Connect mapper registered for this operation; repronig.subject carries the payload.',
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function syncWorkEnvelope(IntegrationOutboxEntry $entry): array
    {
        $subject = $entry->subject;

        if (! $subject instanceof Work) {
            return [
                'schema_version' => 1,
                'operation' => 'sync_work',
                'work' => null,
                'note' => 'Subject is not a Work; only generic subject metadata is sent.',
            ];
        }

        return [
            'schema_version' => 1,
            'operation' => 'sync_work',
            'work' => [
                'id' => $subject->id,
                'reference_number' => $subject->reference_number,
                'external_id' => $subject->external_id,
                'title' => $subject->title,
                'subtitle' => $subject->subtitle,
                'publication_year' => $subject->publication_year,
                'primary_language' => $subject->primary_language,
                'identifier_type' => $subject->identifier_type,
                'identifier_value' => $subject->identifier_value,
                'work_status' => $subject->work_status?->value,
                'verification_status' => $subject->verification_status?->value,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function syncInstitutionEnvelope(IntegrationOutboxEntry $entry): array
    {
        $subject = $entry->subject;

        if (! $subject instanceof Institution) {
            return [
                'schema_version' => 1,
                'operation' => 'sync_institution',
                'institution' => null,
                'note' => 'Subject is not an Institution.',
            ];
        }

        return [
            'schema_version' => 1,
            'operation' => 'sync_institution',
            'institution' => [
                'id' => $subject->id,
                'external_id' => $subject->external_id,
                'name' => $subject->name,
                'institution_type' => $subject->institution_type,
                'email' => $subject->email,
                'account_status' => $subject->account_status,
                'onboarding_status' => $subject->onboarding_status,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function syncMemberEnvelope(IntegrationOutboxEntry $entry): array
    {
        $subject = $entry->subject;

        if (! $subject instanceof Member) {
            return [
                'schema_version' => 1,
                'operation' => 'sync_member',
                'member' => null,
                'note' => 'Subject is not a Member.',
            ];
        }

        return [
            'schema_version' => 1,
            'operation' => 'sync_member',
            'member' => [
                'id' => $subject->id,
                'external_id' => $subject->external_id,
                'member_code' => $subject->member_code,
                'member_type' => $subject->member_type,
                'approval_status' => $subject->approval_status,
                'account_status' => $subject->account_status,
                'user_email' => $subject->user?->email,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function syncLicenceEnvelope(IntegrationOutboxEntry $entry): array
    {
        $subject = $entry->subject;

        if (! $subject instanceof Licence) {
            return [
                'schema_version' => 1,
                'operation' => 'sync_licence',
                'licence' => null,
                'note' => 'Subject is not a Licence.',
            ];
        }

        return [
            'schema_version' => 1,
            'operation' => 'sync_licence',
            'licence' => [
                'id' => $subject->id,
                'institution_id' => $subject->institution_id,
                'licence_number' => $subject->licence_number,
                'licence_year' => $subject->licence_year,
                'licence_status' => $subject->licence_status,
                'payment_status' => $subject->payment_status,
                'amount_due' => $subject->amount_due,
                'outstanding_amount' => $subject->outstanding_amount,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function subjectPayload(IntegrationOutboxEntry $entry): ?array
    {
        $subject = $entry->subject;

        if ($subject instanceof Work) {
            return [
                'type' => 'work',
                'id' => $subject->id,
                'reference_number' => $subject->reference_number,
                'external_id' => $subject->external_id,
                'title' => $subject->title,
                'publication_year' => $subject->publication_year,
                'identifier_type' => $subject->identifier_type,
                'identifier_value' => $subject->identifier_value,
                'work_status' => $subject->work_status?->value,
                'verification_status' => $subject->verification_status?->value,
            ];
        }

        if ($subject instanceof Institution) {
            return [
                'type' => 'institution',
                'id' => $subject->id,
                'name' => $subject->name,
                'account_status' => $subject->account_status,
            ];
        }

        if ($subject instanceof Member) {
            return [
                'type' => 'member',
                'id' => $subject->id,
                'member_code' => $subject->member_code,
                'approval_status' => $subject->approval_status,
                'user_email' => $subject->user?->email,
            ];
        }

        if ($subject instanceof Licence) {
            return [
                'type' => 'licence',
                'id' => $subject->id,
                'licence_number' => $subject->licence_number,
                'licence_status' => $subject->licence_status,
            ];
        }

        if ($subject !== null) {
            return [
                'type' => $subject->getMorphClass(),
                'id' => $subject->getKey(),
            ];
        }

        return null;
    }
}
