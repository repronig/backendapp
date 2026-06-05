<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\ApplicantAssociationMatchesType;
use App\Support\Membership\ApplicantAssociationMap;
use App\Support\Membership\MemberApplicationCategoryMap;
use Illuminate\Validation\Rule;

trait MemberApplicationFieldRules
{
    protected function memberApplicationFieldRules(string $presence = 'sometimes'): array
    {
        $routeApplication = $this->route('memberApplication');
        $applicantType = $this->input('applicant_type', $routeApplication?->applicant_type);
        $memberAuthorType = $this->input('member_author_type', $routeApplication?->member_author_type);

        $isAuthor = $applicantType === 'author';
        $isArtist = $applicantType === 'artist';
        $isPublisher = in_array($applicantType, ['publisher', 'corporate_publisher'], true);
        $requiresMemberType = $isAuthor || $isArtist || $isPublisher;

        $isIndividual = MemberApplicationCategoryMap::isIndividualMemberType($memberAuthorType);
        $isOrgMember = MemberApplicationCategoryMap::isOrgMemberType($memberAuthorType);

        $allowedCategories = MemberApplicationCategoryMap::allowedFor($applicantType);
        $categoryRule = $allowedCategories !== []
            ? Rule::in($allowedCategories)
            : null;

        $memberAuthorCategoryRules = array_values(array_filter([
            Rule::requiredIf($requiresMemberType),
            'nullable',
            'string',
            $categoryRule,
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
            'member_author_type' => [
                Rule::requiredIf($requiresMemberType),
                'nullable',
                'in:individual,corporate,agent',
            ],
            'member_author_category' => $memberAuthorCategoryRules,
            'nationality' => [
                Rule::requiredIf($isIndividual),
                'nullable',
                'string',
                'max:100',
            ],
            'country_of_residence' => [$presence, 'string', 'min:2', 'max:100'],
            'is_diaspora' => ['nullable', 'boolean'],
            'bank_name' => [$presence, 'string', 'max:150'],
            'bank_account_number' => [$presence, 'string', 'max:50'],
            'bank_account_owner_name' => [$presence, 'string', 'max:180'],
            'next_of_kin_name' => [
                Rule::requiredIf($isIndividual),
                'nullable',
                'string',
                'max:180',
            ],
            'next_of_kin_phone' => [
                Rule::requiredIf($isIndividual),
                'nullable',
                'string',
                'max:50',
            ],
            'publisher_organisation_name' => [
                Rule::requiredIf($isOrgMember),
                'nullable',
                'string',
                'max:180',
            ],
            'publisher_tin' => ['nullable', 'string', 'max:80'],
            'publisher_location_address' => [
                Rule::requiredIf($isOrgMember),
                'nullable',
                'string',
                'max:2000',
            ],
            'publisher_postal_address' => [
                Rule::requiredIf($isOrgMember),
                'nullable',
                'string',
                'max:2000',
            ],
            'publisher_email' => [
                Rule::requiredIf($isOrgMember),
                'nullable',
                'email',
                'max:255',
            ],
            'publisher_phone' => [
                Rule::requiredIf($isOrgMember),
                'nullable',
                'string',
                'max:50',
            ],
            'consent_accepted' => [$presence, 'accepted'],
            'consent_date' => [$presence, 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'member_provided_id' => ['nullable', 'string', 'max:100'],
        ];
    }
}
