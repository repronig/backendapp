<?php

use App\Enums\WorkStatus;
use App\Enums\WorkVerificationStatus;
use App\Jobs\SendWorkUpdateRequestedNotificationJob;
use App\Jobs\SendWorkUpdateRequestReviewedNotificationJob;
use App\Mail\Works\WorkReviewDecisionMemberMailable;
use App\Mail\Works\WorkSubmittedAdminMailable;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkContributor;
use App\Models\WorkFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    ensureRole('member');
});

function actingAsMemberWithProfile(): array
{
    $user = actingAsApiUser('member', ['account_type' => 'member']);
    $member = Member::factory()->create([
        'user_id' => $user->id,
        'member_type' => 'author',
        'approval_status' => 'approved',
        'account_status' => 'active',
    ]);

    return [$user, $member];
}

it('creates a work using the strict current work contract', function () {
    [$user, $member] = actingAsMemberWithProfile();

    $response = $this->postJson('/api/v1/works', validWorkPayload([
        'title' => 'My New Work',
        'type_of_work' => 'fiction_text',
        'work_format' => 'hard_digital_copy',
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.member_id', $member->id)
        ->assertJsonPath('data.type_of_work', 'fiction_text')
        ->assertJsonPath('data.title', 'My New Work')
        ->assertJsonMissingPath('data.work_type')
        ->assertJsonMissingPath('data.format_label');

    $this->assertDatabaseHas('works', [
        'member_id' => $member->id,
        'type_of_work' => 'fiction_text',
        'work_format' => 'hard_digital_copy',
        'agreement_accepted' => true,
    ]);
});

it('blocks creating a work with duplicate ISBN or ISSN identifier values', function () {
    [$user, $member] = actingAsMemberWithProfile();

    Work::factory()->create([
        'identifier_type' => 'isbn',
        'identifier_value' => '9783161484100',
    ]);

    $this->postJson('/api/v1/works', validWorkPayload([
        'identifier_type' => 'isbn',
        'identifier_value' => '9783161484100',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['identifier_value']);

    Work::factory()->create([
        'identifier_type' => 'issn',
        'identifier_value' => '2049-3630',
    ]);

    $this->postJson('/api/v1/works', validWorkPayload([
        'identifier_type' => 'issn',
        'identifier_value' => '2049-3630',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['identifier_value']);
});

it('rejects legacy work fields and unsupported work file types', function () {
    actingAsMemberWithProfile();

    $this->postJson('/api/v1/works', validWorkPayload([
        'type_of_work' => null,
        'work_type' => 'book',
        'format_label' => 'PDF',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['type_of_work']);

    $work = Work::factory()->create([
        'member_id' => auth()->user()->member->id,
        'work_status' => 'draft',
    ]);

    Storage::fake('public');

    $this->postJson("/api/v1/works/{$work->id}/files", [
        'file_type' => 'manuscript',
        'file' => UploadedFile::fake()->create('manuscript.pdf', 100, 'application/pdf'),
    ])->assertUnprocessable()->assertJsonValidationErrors(['file_type']);
});

it('allows a member to delete only draft work', function () {
    [$user, $member] = actingAsMemberWithProfile();

    $draftWork = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'draft',
    ]);

    $this->deleteJson("/api/v1/works/{$draftWork->id}")
        ->assertOk();

    $this->assertDatabaseMissing('works', [
        'id' => $draftWork->id,
    ]);

    $submittedWork = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'submitted',
    ]);

    $this->deleteJson("/api/v1/works/{$submittedWork->id}")
        ->assertForbidden();
});

it('allows submitting a work without identifier_value when other requirements are met', function () {
    [$user, $member] = actingAsMemberWithProfile();

    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'draft',
        'type_of_work' => 'educational_non_fiction_scientific_text',
        'work_format' => 'digital_copy',
        'identifier_type' => 'isbn',
        'identifier_value' => null,
        'title' => 'Submission Without Identifier Value',
        'target_market' => 'school_market',
        'production_status' => 'yes',
        'agreement_accepted' => true,
        'date_of_consent' => now()->toDateString(),
    ]);

    WorkContributor::factory()->forMember($member)->create([
        'work_id' => $work->id,
        'ownership_percentage' => 100,
    ]);
    WorkFile::factory()->cover()->forWork($work)->uploadedBy($user)->create();

    $this->postJson("/api/v1/works/{$work->id}/submit")
        ->assertOk()
        ->assertJsonPath('data.work_status', 'submitted')
        ->assertJsonPath('data.verification_status', 'pending');
});

it('blocks submitting a work when identifier_value duplicates another work', function () {
    [$user, $member] = actingAsMemberWithProfile();

    Work::factory()->create([
        'identifier_type' => 'isbn',
        'identifier_value' => '9788888888881',
        'title' => 'Existing Registered Work',
    ]);

    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'draft',
        'type_of_work' => 'educational_non_fiction_scientific_text',
        'work_format' => 'digital_copy',
        'identifier_type' => 'isbn',
        'identifier_value' => '9788888888881',
        'target_market' => 'school_market',
        'production_status' => 'yes',
        'agreement_accepted' => true,
        'date_of_consent' => now()->toDateString(),
    ]);

    WorkContributor::factory()->forMember($member)->create([
        'work_id' => $work->id,
        'ownership_percentage' => 100,
    ]);
    WorkFile::factory()->cover()->forWork($work)->uploadedBy($user)->create();

    $this->postJson("/api/v1/works/{$work->id}/submit")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['identifier_value']);
});

it('requires a cover image and 100 percent contributor ownership before work submission', function () {
    [$user, $member] = actingAsMemberWithProfile();

    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'draft',
        'type_of_work' => 'educational_non_fiction_scientific_text',
        'work_format' => 'digital_copy',
        'identifier_type' => 'isbn',
        'identifier_value' => '9783161484100',
        'target_market' => 'school_market',
        'production_status' => 'yes',
        'agreement_accepted' => true,
        'date_of_consent' => now()->toDateString(),
    ]);

    WorkContributor::factory()->forMember($member)->create([
        'work_id' => $work->id,
        'ownership_percentage' => 50,
    ]);

    $this->postJson("/api/v1/works/{$work->id}/submit")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['files']);

    WorkFile::factory()->cover()->forWork($work)->uploadedBy($user)->create();

    $this->postJson("/api/v1/works/{$work->id}/submit")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ownership_percentage']);

    $work->contributors()->update(['ownership_percentage' => 100]);

    $this->postJson("/api/v1/works/{$work->id}/submit")
        ->assertOk()
        ->assertJsonPath('data.work_status', 'submitted')
        ->assertJsonPath('data.verification_status', 'pending');
});

it('notifies admins by email and system when a work is submitted', function () {
    Mail::fake();
    ensureRole('admin');
    $admin = User::factory()->create([
        'account_type' => 'admin',
        'email' => 'works-admin@example.test',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $admin->assignRole('admin');

    [$user, $member] = actingAsMemberWithProfile();

    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'draft',
        'type_of_work' => 'educational_non_fiction_scientific_text',
        'work_format' => 'digital_copy',
        'identifier_type' => 'isbn',
        'identifier_value' => '9783161484100',
        'target_market' => 'school_market',
        'production_status' => 'yes',
        'agreement_accepted' => true,
        'date_of_consent' => now()->toDateString(),
    ]);

    WorkContributor::factory()->forMember($member)->create([
        'work_id' => $work->id,
        'ownership_percentage' => 100,
    ]);
    WorkFile::factory()->cover()->forWork($work)->uploadedBy($user)->create();

    $this->postJson("/api/v1/works/{$work->id}/submit")
        ->assertOk()
        ->assertJsonPath('data.work_status', 'submitted');

    Mail::assertQueued(WorkSubmittedAdminMailable::class, 1);

    expect(NotificationLog::query()
        ->where('notification_key', 'work_submitted_admin')
        ->where('channel', 'email')
        ->where('user_id', $admin->id)
        ->exists())->toBeTrue()
        ->and(NotificationLog::query()
            ->where('notification_key', 'work_submitted_admin')
            ->where('channel', 'system')
            ->where('user_id', $admin->id)
            ->exists())->toBeTrue();
});

it('notifies a member by email and system when admin reviews a submitted work', function () {
    Mail::fake();
    ensureRole('admin');
    $admin = User::factory()->create([
        'account_type' => 'admin',
        'email' => 'reviewer@example.test',
        'email_verified_at' => now(),
        'last_security_confirmation_at' => now(),
        'status' => 'active',
    ]);
    $admin->assignRole('admin');

    [$memberUser, $member] = actingAsMemberWithProfile();

    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'submitted',
        'verification_status' => 'pending',
        'title' => 'Reviewed Work',
    ]);

    Sanctum::actingAs($admin);
    $this->postJson("/api/v1/admin/works/{$work->id}/review", [
        'decision' => 'changes_requested',
        'review_note' => 'Please upload a clearer proof of ownership document.',
    ])->assertOk()
        ->assertJsonPath('data.work_status', 'changes_requested');

    Mail::assertQueued(WorkReviewDecisionMemberMailable::class, 1);

    expect(NotificationLog::query()
        ->where('notification_key', 'work_reviewed_member')
        ->where('channel', 'email')
        ->where('user_id', $memberUser->id)
        ->exists())->toBeTrue()
        ->and(NotificationLog::query()
            ->where('notification_key', 'work_reviewed_member')
            ->where('channel', 'system')
            ->where('user_id', $memberUser->id)
            ->exists())->toBeTrue();
});

it('notifies member and sets work to changes requested on contributor dispute, then notifies admin on resubmission', function () {
    Mail::fake();
    ensureRole('admin');
    $admin = User::factory()->create([
        'account_type' => 'admin',
        'email' => 'dispute-admin@example.test',
        'email_verified_at' => now(),
        'last_security_confirmation_at' => now(),
        'status' => 'active',
    ]);
    $admin->assignRole('admin');

    [$memberUser, $member] = actingAsMemberWithProfile();
    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'submitted',
        'verification_status' => 'pending',
        'title' => 'Disputed Contributor Work',
        'type_of_work' => 'educational_non_fiction_scientific_text',
        'work_format' => 'digital_copy',
        'identifier_type' => 'isbn',
        'identifier_value' => '9783161484100',
        'target_market' => 'school_market',
        'production_status' => 'yes',
        'agreement_accepted' => true,
        'date_of_consent' => now()->toDateString(),
    ]);
    $contributor = WorkContributor::factory()->forMember($member)->create([
        'work_id' => $work->id,
        'ownership_percentage' => 100,
    ]);
    WorkFile::factory()->cover()->forWork($work)->uploadedBy($memberUser)->create();

    Sanctum::actingAs($admin);
    $this->postJson("/api/v1/admin/works/{$work->id}/contributors/{$contributor->id}/dispute", [
        'reason_code' => 'ownership_conflict',
        'reason' => 'Contributor ownership details conflict with submitted records.',
    ])->assertOk();

    $this->assertDatabaseHas('works', [
        'id' => $work->id,
        'work_status' => 'changes_requested',
        'verification_status' => 'pending',
    ]);

    Mail::assertQueued(WorkReviewDecisionMemberMailable::class, 1);
    expect(NotificationLog::query()
        ->where('notification_key', 'work_reviewed_member')
        ->where('channel', 'email')
        ->where('user_id', $memberUser->id)
        ->exists())->toBeTrue()
        ->and(NotificationLog::query()
            ->where('notification_key', 'work_reviewed_member')
            ->where('channel', 'system')
            ->where('user_id', $memberUser->id)
            ->exists())->toBeTrue();

    Sanctum::actingAs($memberUser);
    $this->patchJson("/api/v1/works/{$work->id}", validWorkPayload([
        'title' => 'Disputed Contributor Work - Updated',
    ]))->assertOk()
        ->assertJsonPath('data.work_status', 'changes_requested');

    $this->postJson("/api/v1/works/{$work->id}/submit")
        ->assertOk()
        ->assertJsonPath('data.work_status', 'submitted');

    Mail::assertQueued(WorkSubmittedAdminMailable::class, 1);
    expect(NotificationLog::query()
        ->where('notification_key', 'work_submitted_admin')
        ->where('channel', 'email')
        ->where('user_id', $admin->id)
        ->exists())->toBeTrue()
        ->and(NotificationLog::query()
            ->where('notification_key', 'work_submitted_admin')
            ->where('channel', 'system')
            ->where('user_id', $admin->id)
            ->exists())->toBeTrue();
});

it('stores only the current related work file types', function () {
    [$user, $member] = actingAsMemberWithProfile();
    Storage::fake('public');

    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'draft',
    ]);

    $this->postJson("/api/v1/works/{$work->id}/files", [
        'file_type' => 'cover_image',
        'file' => UploadedFile::fake()->image('cover.jpg'),
    ])->assertCreated()->assertJsonPath('data.file_type', 'cover_image');

    $this->postJson("/api/v1/works/{$work->id}/files", [
        'file_type' => 'copyright_page',
        'file' => UploadedFile::fake()->create('copyright.pdf', 100, 'application/pdf'),
    ])->assertCreated()->assertJsonPath('data.file_type', 'copyright_page');

    $this->postJson("/api/v1/works/{$work->id}/files", [
        'file_type' => 'proof_of_ownership',
        'file' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
    ])->assertCreated()->assertJsonPath('data.file_type', 'proof_of_ownership');

    expect($work->files()->count())->toBe(3);
});

it('includes dispute metadata on work contributors in the api payload', function () {
    [$user, $member] = actingAsMemberWithProfile();

    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'draft',
    ]);

    WorkContributor::factory()->create([
        'work_id' => $work->id,
        'member_id' => $member->id,
        'contributor_name' => 'Primary Author',
        'contributor_role' => 'author',
        'right_type' => 'exclusive',
        'ownership_percentage' => 100,
        'is_disputed' => true,
        'dispute_reason_code' => 'rights_dispute',
        'dispute_reason' => 'Overlapping rights claim.',
        'disputed_by_user_id' => $user->id,
        'disputed_at' => now(),
    ]);

    $this->getJson("/api/v1/works/{$work->id}")
        ->assertOk()
        ->assertJsonPath('data.contributors.0.is_disputed', true)
        ->assertJsonPath('data.contributors.0.dispute_reason_code', 'rights_dispute')
        ->assertJsonPath('data.contributors.0.disputed_by_user_id', $user->id);
});

it('filters works by disputed status when a contributor is disputed', function () {
    [$user, $member] = actingAsMemberWithProfile();

    $disputedWork = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'changes_requested',
        'is_disputed' => false,
    ]);
    WorkContributor::factory()->create([
        'work_id' => $disputedWork->id,
        'member_id' => $member->id,
        'is_disputed' => true,
    ]);

    $otherWork = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'draft',
        'is_disputed' => false,
    ]);

    $this->getJson('/api/v1/works?status=disputed')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $disputedWork->id);
});

it('casts work_status and verification_status to enums on the Work model', function () {
    [$user, $member] = actingAsMemberWithProfile();

    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'draft',
        'verification_status' => 'pending',
    ]);

    expect($work->work_status)->toBe(WorkStatus::Draft)
        ->and($work->verification_status)->toBe(WorkVerificationStatus::Pending);
});

it('allows a member to request update access for an approved work and dispatches notifications', function () {
    Queue::fake();
    [$user, $member] = actingAsMemberWithProfile();

    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'approved',
        'verification_status' => 'verified',
        'is_restricted' => false,
        'update_request_status' => null,
    ]);

    $this->postJson("/api/v1/works/{$work->id}/request-update", [
        'note' => 'Please allow correction to contributor ownership details.',
    ])->assertOk()
        ->assertJsonPath('data.update_request_status', 'pending');

    $this->assertDatabaseHas('works', [
        'id' => $work->id,
        'update_request_status' => 'pending',
        'update_requested_by_member_id' => $member->id,
    ]);

    Queue::assertPushed(SendWorkUpdateRequestedNotificationJob::class);
});

it('approves a pending work update request and unlocks draft editing', function () {
    Queue::fake();
    ensureRole('admin');
    $admin = User::factory()->create([
        'account_type' => 'admin',
        'email_verified_at' => now(),
        'last_security_confirmation_at' => now(),
    ]);
    $admin->assignRole('admin');

    [$memberUser, $member] = actingAsMemberWithProfile();
    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'approved',
        'verification_status' => 'verified',
        'update_request_status' => 'pending',
        'update_requested_at' => now(),
        'update_requested_by_member_id' => $member->id,
    ]);

    Sanctum::actingAs($admin);
    $this->postJson("/api/v1/admin/works/{$work->id}/update-request/review", [
        'decision' => 'approved',
        'review_note' => 'Proceed with correction and resubmission.',
    ])->assertOk()
        ->assertJsonPath('data.work_status', 'draft')
        ->assertJsonPath('data.verification_status', 'pending')
        ->assertJsonPath('data.update_request_status', 'approved');

    $this->assertDatabaseHas('works', [
        'id' => $work->id,
        'work_status' => 'draft',
        'verification_status' => 'pending',
        'update_request_status' => 'approved',
        'update_request_reviewed_by_user_id' => $admin->id,
    ]);

    Queue::assertPushed(SendWorkUpdateRequestReviewedNotificationJob::class);

    Sanctum::actingAs($memberUser);
    $this->patchJson("/api/v1/works/{$work->id}", validWorkPayload([
        'title' => 'Updated title after approval gate',
    ]))->assertOk()
        ->assertJsonPath('data.title', 'Updated title after approval gate')
        ->assertJsonPath('data.work_status', 'draft');
});

it('rejects a pending work update request and keeps approved work locked', function () {
    Queue::fake();
    ensureRole('admin');
    $admin = User::factory()->create([
        'account_type' => 'admin',
        'email_verified_at' => now(),
        'last_security_confirmation_at' => now(),
    ]);
    $admin->assignRole('admin');

    [$memberUser, $member] = actingAsMemberWithProfile();
    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'approved',
        'verification_status' => 'verified',
        'update_request_status' => 'pending',
        'update_requested_at' => now(),
        'update_requested_by_member_id' => $member->id,
    ]);

    Sanctum::actingAs($admin);
    $this->postJson("/api/v1/admin/works/{$work->id}/update-request/review", [
        'decision' => 'rejected',
        'review_note' => 'Insufficient reason for reopening this approved work.',
    ])->assertOk()
        ->assertJsonPath('data.work_status', 'approved')
        ->assertJsonPath('data.update_request_status', 'rejected');

    $this->assertDatabaseHas('works', [
        'id' => $work->id,
        'work_status' => 'approved',
        'update_request_status' => 'rejected',
        'update_request_reviewed_by_user_id' => $admin->id,
    ]);

    Queue::assertPushed(SendWorkUpdateRequestReviewedNotificationJob::class);

    Sanctum::actingAs($memberUser);
    $this->patchJson("/api/v1/works/{$work->id}", validWorkPayload([
        'title' => 'Blocked title update',
    ]))->assertForbidden();
});
