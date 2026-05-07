<?php

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\Mail\MailService;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    ensureRole('member');
});

it('deduplicates email deliveries with identical idempotency context', function () {
    Mail::fake();

    $service = app(MailService::class);

    $mailable = new class extends Mailable
    {
        public function build(): self
        {
            return $this->subject('Demo')->html('<p>Demo</p>');
        }
    };

    $service->sendMailable(
        null,
        'member@example.test',
        'payment_initiated',
        'Payment initiated',
        $mailable,
        ['entity_type' => 'payment', 'entity_id' => 42]
    );

    $service->sendMailable(
        null,
        'member@example.test',
        'payment_initiated',
        'Payment initiated',
        $mailable,
        ['entity_type' => 'payment', 'entity_id' => 42]
    );

    Mail::assertQueued($mailable::class, 1);
    expect(NotificationLog::query()->where('notification_key', 'payment_initiated')->count())->toBe(1);
});

it('deduplicates system deliveries with identical idempotency context', function () {
    $user = User::factory()->create();
    $service = app(SystemNotificationService::class);

    $notification = new class extends Notification
    {
        public function via(object $notifiable): array
        {
            return ['database'];
        }

        public function toArray(object $notifiable): array
        {
            return [
                'type' => 'invoice_due_reminder',
                'title' => 'Invoice due reminder',
                'message' => 'Invoice INV-001 is due.',
            ];
        }
    };

    $service->send($user, $notification, 'invoice_due_reminder', 'Invoice due reminder', ['entity_type' => 'invoice', 'entity_id' => 999]);
    $service->send($user, $notification, 'invoice_due_reminder', 'Invoice due reminder', ['entity_type' => 'invoice', 'entity_id' => 999]);

    expect(NotificationLog::query()
        ->where('user_id', $user->id)
        ->where('channel', 'system')
        ->where('notification_key', 'invoice_due_reminder')
        ->count())->toBe(1);
});
