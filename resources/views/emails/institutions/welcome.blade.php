@component('emails.layouts.app')
@slot('subject') Welcome to REPRONIG @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $recipientName ?? $institution->contact_person_name ?? 'there' }},</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Welcome to REPRONIG. Your institution account for {{ $institution->name }} is now verified.
</p>
<p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    You can submit annual declarations, manage invoices and payments, and track licensing updates from your institution dashboard.
</p>
<p style="margin:0 0 24px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Complete your onboarding details and begin your first declaration to get started.
</p>
<p style="margin:0 0 24px; font-size:16px; line-height:1.7;">
    <a href="{{ $platformUrl }}" style="display:inline-block; padding:12px 18px; background:#b01217; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:700;">Go to institution dashboard</a>
</p>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Thanks for joining REPRONIG.
</p>
@endcomponent
