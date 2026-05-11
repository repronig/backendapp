<?php

use App\Jobs\SendSupportTicketStaffReplyUserNotificationJob;
use App\Jobs\SendSupportTicketSubmittedNotificationsJob;
use App\Jobs\SendSupportTicketUserReplyAdminNotificationsJob;
use App\Models\SupportTicket;
use App\Models\SupportTicketInternalNote;
use App\Models\User;
use App\Notifications\System\SupportTicketSubmittedUserSystemNotification;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    ensureRole('member');
    ensureRole('association_officer');
    ensureRole('institution_user');
    ensureRole('admin');
    ensureRole('super_admin');
});

it('allows a member to create and list their support tickets', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);

    $create = $this->postJson('/api/v1/support-tickets', [
        'portal_context' => 'member',
        'subject' => 'Cannot upload work file',
        'body' => 'The form returns an error when I attach PDF.',
        'category' => 'technical_issue_or_error',
    ]);

    $create->assertCreated()
        ->assertJsonPath('data.subject', 'Cannot upload work file')
        ->assertJsonPath('data.category', 'technical_issue_or_error')
        ->assertJsonPath('data.status', 'open');

    $ticketId = $create->json('data.id');
    expect($ticketId)->toBeInt();

    $this->getJson('/api/v1/support-tickets')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.per_page', 10);

    $this->getJson("/api/v1/support-tickets/{$ticketId}")
        ->assertOk()
        ->assertJsonMissingPath('data.internal_notes');
});

it('rejects portal_context the user role does not have', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/support-tickets', [
        'portal_context' => 'institution',
        'subject' => 'Wrong portal',
        'body' => 'Test',
        'category' => 'other',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['portal_context']);
});

it('denies a member from viewing another users ticket', function () {
    $owner = User::factory()->create([
        'account_type' => 'member',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $owner->assignRole('member');

    $ticket = SupportTicket::factory()->create(['user_id' => $owner->id]);

    actingAsApiUser('member', ['account_type' => 'member']);

    $this->getJson("/api/v1/support-tickets/{$ticket->id}")->assertForbidden();
});

it('allows the ticket owner to add a reply from the portal', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);
    $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

    $this->postJson("/api/v1/support-tickets/{$ticket->id}/replies", [
        'body' => 'Additional detail: happens on Chrome only.',
    ])->assertCreated()
        ->assertJsonPath('data.is_staff', false);

    $this->getJson("/api/v1/support-tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.replies.0.body', 'Additional detail: happens on Chrome only.');
});

it('allows an admin to list tickets, update status, reply as staff, and add internal notes', function () {
    $owner = User::factory()->create([
        'account_type' => 'member',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $owner->assignRole('member');

    $ticket = SupportTicket::factory()->create([
        'user_id' => $owner->id,
        'subject' => 'Billing question',
    ]);

    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/support-tickets')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.per_page', 10);

    $this->getJson("/api/v1/admin/support-tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.subject', 'Billing question');

    $this->patchJson("/api/v1/admin/support-tickets/{$ticket->id}", [
        'status' => 'in_progress',
    ])->assertOk()
        ->assertJsonPath('data.status', 'in_progress');

    $this->postJson("/api/v1/admin/support-tickets/{$ticket->id}/replies", [
        'body' => 'We are looking into this.',
    ])->assertCreated()
        ->assertJsonPath('data.is_staff', true);

    $this->postJson("/api/v1/admin/support-tickets/{$ticket->id}/internal-notes", [
        'body' => 'Escalated to finance — internal only.',
    ])->assertCreated()
        ->assertJsonPath('data.body', 'Escalated to finance — internal only.');

    $this->getJson("/api/v1/admin/support-tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonPath('data.internal_notes.0.body', 'Escalated to finance — internal only.');

    actingAsApiUser('member', ['account_type' => 'member']);
    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/support-tickets/{$ticket->id}")
        ->assertOk()
        ->assertJsonMissingPath('data.internal_notes.0');
});

it('denies a member from calling admin internal note endpoint', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);
    $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

    $this->postJson("/api/v1/admin/support-tickets/{$ticket->id}/internal-notes", [
        'body' => 'Hacked note',
    ])->assertForbidden();
});

it('denies a member from admin support ticket list and detail routes', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);
    $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

    $this->getJson('/api/v1/admin/support-tickets')->assertForbidden();
    $this->getJson("/api/v1/admin/support-tickets/{$ticket->id}")->assertForbidden();
});

it('uses a single staff inbox path for admin and super_admin alert recipients', function () {
    $admin = User::factory()->create([
        'account_type' => 'admin',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $admin->assignRole('admin');

    $super = User::factory()->create([
        'account_type' => 'super_admin',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $super->assignRole('super_admin');

    expect($admin->adminSupportTicketsInboxPath())->toBe('/admin/support')
        ->and($super->adminSupportTicketsInboxPath())->toBe('/admin/support');
});

it('does not expose internal notes on the portal ticket payload', function () {
    $owner = User::factory()->create([
        'account_type' => 'member',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $owner->assignRole('member');

    $admin = User::factory()->create([
        'account_type' => 'admin',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $admin->assignRole('admin');

    $ticket = SupportTicket::factory()->create(['user_id' => $owner->id]);

    SupportTicketInternalNote::factory()->create([
        'support_ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'body' => 'Secret internal text',
    ]);

    Sanctum::actingAs($owner);

    $response = $this->getJson("/api/v1/support-tickets/{$ticket->id}")->assertOk();
    expect($response->json('data'))->not->toHaveKey('internal_notes');
});

it('dispatches submitted ticket notification job when a member creates a ticket', function () {
    Bus::fake();
    actingAsApiUser('member', ['account_type' => 'member']);

    $create = $this->postJson('/api/v1/support-tickets', [
        'portal_context' => 'member',
        'subject' => 'Queued job test',
        'body' => 'Body',
        'category' => 'other',
    ])->assertCreated();

    $ticketId = (int) $create->json('data.id');
    expect($ticketId)->toBeGreaterThan(0);

    Bus::assertDispatched(SendSupportTicketSubmittedNotificationsJob::class, function (SendSupportTicketSubmittedNotificationsJob $job) use ($ticketId): bool {
        return $job->supportTicketId === $ticketId;
    });
});

it('dispatches staff reply notification job when an admin replies', function () {
    Bus::fake();
    $owner = User::factory()->create([
        'account_type' => 'member',
        'email_verified_at' => now(),
        'status' => 'active',
    ]);
    $owner->assignRole('member');
    $ticket = SupportTicket::factory()->create(['user_id' => $owner->id]);

    actingAsApiUser('admin', ['account_type' => 'admin']);

    $reply = $this->postJson("/api/v1/admin/support-tickets/{$ticket->id}/replies", [
        'body' => 'Staff follow-up',
    ])->assertCreated();

    $replyId = (int) $reply->json('data.id');

    Bus::assertDispatched(SendSupportTicketStaffReplyUserNotificationJob::class, function (SendSupportTicketStaffReplyUserNotificationJob $job) use ($replyId): bool {
        return $job->supportTicketReplyId === $replyId;
    });
});

it('dispatches user reply admin notification job when the owner replies from the portal', function () {
    Bus::fake();
    $user = actingAsApiUser('member', ['account_type' => 'member']);
    $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

    $reply = $this->postJson("/api/v1/support-tickets/{$ticket->id}/replies", [
        'body' => 'More info from me',
    ])->assertCreated();

    $replyId = (int) $reply->json('data.id');

    Bus::assertDispatched(SendSupportTicketUserReplyAdminNotificationsJob::class, function (SendSupportTicketUserReplyAdminNotificationsJob $job) use ($replyId): bool {
        return $job->supportTicketReplyId === $replyId;
    });
});

it('includes a ticket deep link on the submitted ticket user notification', function () {
    Notification::fake();

    $user = actingAsApiUser('member', ['account_type' => 'member']);

    $create = $this->postJson('/api/v1/support-tickets', [
        'portal_context' => 'member',
        'subject' => 'Deep link test',
        'body' => 'Body',
        'category' => 'other',
    ])->assertCreated();

    $ticketId = (int) $create->json('data.id');

    $ref = SupportTicket::formattedReference($ticketId);

    Notification::assertSentTo($user, SupportTicketSubmittedUserSystemNotification::class, function (SupportTicketSubmittedUserSystemNotification $n) use ($user, $ticketId, $ref): bool {
        $payload = $n->toArray($user);

        return isset($payload['action_url'], $payload['title'], $payload['message'])
            && str_contains((string) $payload['action_url'], '/member/support/'.$ticketId)
            && str_contains((string) $payload['title'], $ref)
            && str_contains((string) $payload['message'], $ref);
    });
});
