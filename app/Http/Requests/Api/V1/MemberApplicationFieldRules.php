<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\ApplicantAssociationMatchesType;
use App\Support\Membership\ApplicantAssociationMap;
use Illuminate\Validation\Rule;

trait MemberApplicationFieldRules
{
    protected function memberApplicationFieldRules(string $presence = 'sometimes'): array
    {
        $routeApplication = $this->route('memberApplication');
        $applicantType = $this->input('applicant_type', $routeApplication?->applicant_type);
        $isAuthor = $applicantType === 'author';
        $isArtist = $applicantType === 'artist';
        $isAuthorLike = $isAuthor || $isArtist;
        $isPublisher = in_array($applicantType, ['publisher', 'corporate_publisher'], true);

        $authorCategoryRule = $isAuthor
            ? Rule::in(['author', 'journalist', 'photographer', 'illustrator', 'carver', 'painter', 'sculptor', 'other'])
            : null;

        $artistCategoryRule = $isArtist
            ? Rule::in(['illustrator', 'carver', 'painter', 'sculptor', 'other'])
            : null;

        $memberAuthorCategoryRules = array_values(array_filter([
            Rule::requiredIf($isAuthorLike),
            'nullable',
            'string',
            $authorCategoryRule,
            $artistCategoryRule,
        ]));

        return [
            'first_name' => [$presence, 'string', 'min:1', 'max:100'],
            'last_name' => [$presence, 'string', 'min:1', 'max:100'],
            'association_id' => [
                $presence,
                'integer',
                'exists:associations,id',
                new ApplicantAssociationMatchesType,
            ],
            'applicant_type' => [
                $presence,
                Rule::in(ApplicantAssociationMap::APPLICANT_TYPES),
            ],
            'member_author_type' => [Rule::requiredIf($isAuthorLike), 'nullable', 'in:individual,corporate,agent'],
            'member_author_category' => $memberAuthorCategoryRules,
            'nationality' => [$presence, 'string', 'max:100'],
            'country_of_residence' => [$presence, 'string', 'max:100'],
            'is_diaspora' => ['nullable', 'boolean'],
            'bank_name' => [$presence, 'string', 'max:150'],
            'bank_account_number' => [$presence, 'string', 'max:50'],
            'bank_account_owner_name' => [$presence, 'string', 'max:180'],
            'next_of_kin_name' => [Rule::requiredIf($isAuthorLike), 'nullable', 'string', 'max:180'],
            'next_of_kin_phone' => [Rule::requiredIf($isAuthorLike), 'nullable', 'string', 'max:50'],
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
