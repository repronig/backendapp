<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

trait MemberApplicationFieldRules
{
    protected function memberApplicationFieldRules(string $presence = 'sometimes'): array
    {
        $routeApplication = $this->route('memberApplication');
        $applicantType = $this->input('applicant_type', $routeApplication?->applicant_type);
        $isAuthor = $applicantType === 'author';
        $isPublisher = in_array($applicantType, ['publisher', 'corporate_publisher'], true);

        return [
            'first_name' => [$presence, 'string', 'min:1', 'max:100'],
            'last_name' => [$presence, 'string', 'min:1', 'max:100'],
            'association_id' => [$presence, 'integer', 'exists:associations,id'],
            'applicant_type' => [$presence, 'in:author,publisher,corporate_publisher'],
            'member_author_type' => [Rule::requiredIf($isAuthor), 'nullable', 'in:individual,corporate,agent'],
            'member_author_category' => [Rule::requiredIf($isAuthor), 'nullable', 'in:author,journalist,photographer,illustrator,carver,painter,sculptor,other'],
            'nationality' => [$presence, 'string', 'max:100'],
            'country_of_residence' => [$presence, 'string', 'max:100'],
            'is_diaspora' => ['nullable', 'boolean'],
            'bank_name' => [$presence, 'string', 'max:150'],
            'bank_account_number' => [$presence, 'string', 'max:50'],
            'bank_account_owner_name' => [$presence, 'string', 'max:180'],
            'next_of_kin_name' => [Rule::requiredIf($isAuthor), 'nullable', 'string', 'max:180'],
            'next_of_kin_phone' => [Rule::requiredIf($isAuthor), 'nullable', 'string', 'max:50'],
            'publisher_organisation_name' => [Rule::requiredIf($isPublisher), 'nullable', 'string', 'max:180'],
            'publisher_tin' => [Rule::requiredIf($isPublisher), 'nullable', 'string', 'max:80'],
            'publisher_location_address' => [Rule::requiredIf($isPublisher), 'nullable', 'string', 'max:2000'],
            'publisher_postal_address' => [Rule::requiredIf($isPublisher), 'nullable', 'string', 'max:2000'],
            'publisher_email' => [Rule::requiredIf($isPublisher), 'nullable', 'email', 'max:255'],
            'publisher_phone' => [Rule::requiredIf($isPublisher), 'nullable', 'string', 'max:50'],
            'consent_accepted' => [$presence, 'accepted'],
            'consent_date' => [$presence, 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'member_provided_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
