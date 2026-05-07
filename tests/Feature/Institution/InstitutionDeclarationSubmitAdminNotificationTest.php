<?php

use App\Mail\Admin\InstitutionDeclarationSubmittedAdminMailable;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    ensureRole('institution_user');
    ensureRole('admin');
    ensureRole('super_admin');
});

it('queues admin email and creates a system notification when an institution submits a declaration', function () {
    Mail::fake();

    $admin = User::factory()->create([
        'account_type' => 'admin',
        'email' => 'licensing-admin@example.test',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $admin->assignRole('admin');

    [$user, $institution] = actingAsInstitutionUserWithInstitution();

    $declaration = InstitutionAnnualDeclaration::factory()->withSupportingDocument()->create([
        'institution_id' => $institution->id,
    ]);

    $this->postJson("/api/v1/institution/declarations/{$declaration->id}/submit")
        ->assertOk()
        ->assertJsonPath('message', 'Institution annual declaration submitted successfully.');

    Mail::assertQueued(InstitutionDeclarationSubmittedAdminMailable::class, 1);

    expect(NotificationLog::query()->where('notification_key', 'institution_declaration_submitted')->where('channel', 'email')->where('user_id', $admin->id)->exists())->toBeTrue()
        ->and(NotificationLog::query()->where('notification_key', 'institution_declaration_submitted')->where('channel', 'system')->where('user_id', $admin->id)->exists())->toBeTrue()
        ->and($admin->fresh()->notifications()->count())->toBe(1);
});
