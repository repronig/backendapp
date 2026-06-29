<?php

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberApplication;
use App\Models\MemberApplicationDocument;
use App\Models\User;
use App\Models\Work;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    ensureRole('admin');
    ensureRole('member');
    Storage::fake((string) config('filesystems.default', 'local'));
});

function actingAsConfirmedAdmin(): User
{
    $admin = actingAsApiUser('admin', ['account_type' => 'admin']);

    $admin->forceFill(['last_security_confirmation_at' => now()])->save();

    return $admin;
}

it('requires security confirmation to delete a member', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);
    $member = Member::factory()->create();

    $this->deleteJson("/api/v1/admin/members/{$member->id}")
        ->assertStatus(423);
});

it('deletes a member with application works and stored files', function () {
    actingAsConfirmedAdmin();

    $memberUser = User::factory()->create([
        'account_type' => 'member',
        'email_verified_at' => now(),
    ]);
    $memberUser->assignRole('member');

    $application = MemberApplication::factory()->create([
        'user_id' => $memberUser->id,
        'application_status' => 'approved',
    ]);

    $docPath = 'member-applications/test-proof.pdf';
    Storage::disk((string) config('filesystems.default', 'local'))->put($docPath, 'proof');
    MemberApplicationDocument::factory()->forApplication($application)->create([
        'document_type' => 'proof_of_id',
        'file_path' => $docPath,
        'uploaded_by_user_id' => $memberUser->id,
    ]);

    $member = Member::factory()->create([
        'user_id' => $memberUser->id,
    ]);

    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'approved',
    ]);
    $workFilePath = 'works/test-cover.jpg';
    Storage::disk((string) config('filesystems.default', 'local'))->put($workFilePath, 'cover');
    $work->files()->create([
        'file_type' => 'cover_image',
        'file_path' => $workFilePath,
        'file_name' => 'cover.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 5,
        'uploaded_by_user_id' => $memberUser->id,
    ]);

    $this->deleteJson("/api/v1/admin/members/{$member->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Member and associated records deleted successfully.');

    expect(User::query()->whereKey($memberUser->id)->exists())->toBeFalse()
        ->and(Member::query()->whereKey($member->id)->exists())->toBeFalse()
        ->and(MemberApplication::query()->whereKey($application->id)->exists())->toBeFalse()
        ->and(Work::query()->whereKey($work->id)->exists())->toBeFalse()
        ->and(Storage::disk((string) config('filesystems.default', 'local'))->exists($docPath))->toBeFalse()
        ->and(Storage::disk((string) config('filesystems.default', 'local'))->exists($workFilePath))->toBeFalse();

    expect(AuditLog::query()->where('action', 'member_portal_user_deleted')->exists())->toBeTrue();
});

it('requires security confirmation to delete a member application', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);
    $application = MemberApplication::factory()->create();

    $this->deleteJson("/api/v1/admin/member-applications/{$application->id}")
        ->assertStatus(423);
});

it('deletes an approved member application with member works and stored files', function () {
    actingAsConfirmedAdmin();

    $memberUser = User::factory()->create([
        'account_type' => 'member',
        'email_verified_at' => now(),
    ]);
    $memberUser->assignRole('member');

    $application = MemberApplication::factory()->create([
        'user_id' => $memberUser->id,
        'application_status' => 'approved',
    ]);

    $docPath = 'member-applications/approved-proof.pdf';
    Storage::disk((string) config('filesystems.default', 'local'))->put($docPath, 'proof');
    MemberApplicationDocument::factory()->forApplication($application)->create([
        'document_type' => 'proof_of_id',
        'file_path' => $docPath,
        'uploaded_by_user_id' => $memberUser->id,
    ]);

    $member = Member::factory()->create([
        'user_id' => $memberUser->id,
    ]);

    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'approved',
    ]);
    $workFilePath = 'works/application-delete-cover.jpg';
    Storage::disk((string) config('filesystems.default', 'local'))->put($workFilePath, 'cover');
    $work->files()->create([
        'file_type' => 'cover_image',
        'file_path' => $workFilePath,
        'file_name' => 'cover.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 5,
        'uploaded_by_user_id' => $memberUser->id,
    ]);

    $this->deleteJson("/api/v1/admin/member-applications/{$application->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Member application and associated records deleted successfully.');

    expect(User::query()->whereKey($memberUser->id)->exists())->toBeFalse()
        ->and(Member::query()->whereKey($member->id)->exists())->toBeFalse()
        ->and(MemberApplication::query()->whereKey($application->id)->exists())->toBeFalse()
        ->and(Work::query()->whereKey($work->id)->exists())->toBeFalse()
        ->and(Storage::disk((string) config('filesystems.default', 'local'))->exists($docPath))->toBeFalse()
        ->and(Storage::disk((string) config('filesystems.default', 'local'))->exists($workFilePath))->toBeFalse();
});

it('deletes a member application without an approved member record', function () {
    actingAsConfirmedAdmin();

    $memberUser = User::factory()->create([
        'account_type' => 'member',
        'email_verified_at' => now(),
    ]);
    $memberUser->assignRole('member');

    $application = MemberApplication::factory()->create([
        'user_id' => $memberUser->id,
        'application_status' => 'submitted',
    ]);

    $docPath = 'member-applications/test-address.pdf';
    Storage::disk((string) config('filesystems.default', 'local'))->put($docPath, 'address');
    MemberApplicationDocument::factory()->forApplication($application)->create([
        'document_type' => 'proof_of_address',
        'file_path' => $docPath,
        'uploaded_by_user_id' => $memberUser->id,
    ]);

    $this->deleteJson("/api/v1/admin/member-applications/{$application->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Member application and associated records deleted successfully.');

    expect(User::query()->whereKey($memberUser->id)->exists())->toBeFalse()
        ->and(MemberApplication::query()->whereKey($application->id)->exists())->toBeFalse()
        ->and(Member::query()->where('user_id', $memberUser->id)->exists())->toBeFalse()
        ->and(Storage::disk((string) config('filesystems.default', 'local'))->exists($docPath))->toBeFalse();
});

it('deletes an approved work for admin regardless of status', function () {
    actingAsConfirmedAdmin();

    $work = Work::factory()->create(['work_status' => 'approved']);
    $filePath = 'works/admin-delete.pdf';
    Storage::disk((string) config('filesystems.default', 'local'))->put($filePath, 'proof');
    $work->files()->create([
        'file_type' => 'proof_of_ownership',
        'file_path' => $filePath,
        'file_name' => 'proof.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 10,
    ]);

    $this->deleteJson("/api/v1/admin/works/{$work->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Work deleted successfully.');

    expect(Work::query()->whereKey($work->id)->exists())->toBeFalse()
        ->and(Storage::disk((string) config('filesystems.default', 'local'))->exists($filePath))->toBeFalse();
});

it('requires security confirmation to delete a work', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);
    $work = Work::factory()->create();

    $this->deleteJson("/api/v1/admin/works/{$work->id}")
        ->assertStatus(423);
});
