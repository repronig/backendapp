<?php

namespace App\Actions\Members;

use App\Actions\Audit\LogAuditAction;
use App\Models\Member;
use App\Models\MemberProfile;
use App\Models\User;

class UpdateMemberProfileAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        Member $member,
        array $data,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): MemberProfile {
        $syncMemberProvidedId = array_key_exists('member_provided_id', $data);
        $memberProvidedIdValue = null;
        if ($syncMemberProvidedId) {
            $raw = $data['member_provided_id'];
            $memberProvidedIdValue = is_string($raw) && trim($raw) === '' ? null : $raw;
            unset($data['member_provided_id']);
        }

        $userNameFields = [];
        foreach (['first_name', 'last_name'] as $key) {
            if (array_key_exists($key, $data)) {
                $userNameFields[$key] = $data[$key];
                unset($data[$key]);
            }
        }
        if ($userNameFields !== [] && $member->user) {
            $member->user->update($userNameFields);
        }

        $existing = $member->profile;
        $before = $existing?->toArray();

        $profile = MemberProfile::updateOrCreate(
            ['member_id' => $member->id],
            $data
        );

        if ($syncMemberProvidedId) {
            $member->update(['member_provided_id' => $memberProvidedIdValue]);
        }

        $fresh = $profile->fresh();

        $this->logAuditAction->execute(
            $actor,
            $before ? 'member_profile_updated' : 'member_profile_created',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}
