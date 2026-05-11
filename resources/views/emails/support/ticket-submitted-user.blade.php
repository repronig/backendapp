@component('emails.layouts.app')
@slot('subject') Support request {{ \App\Models\SupportTicket::formattedReference((int) ($ticket?->id ?? 0)) }} received @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $ticket?->user?->name ?? 'there' }},</p>
<p style="margin:0 0 12px; font-size:15px; line-height:1.6; color:#667085;">Ticket reference: <strong style="color:#101828;">{{ \App\Models\SupportTicket::formattedReference((int) ($ticket?->id ?? 0)) }}</strong></p>
<p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Thank you for contacting REPRONIG. We have received your support request
    @if($ticket?->subject)
        <strong>“{{ $ticket->subject }}”</strong>
    @endif
    and our team will review it shortly.
</p>
<p style="margin:0 0 24px; font-size:16px; line-height:1.7;">
    <a href="{{ $ticketUrl }}" style="display:inline-block; padding:12px 18px; background:#b01217; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:700;">View your ticket</a>
</p>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    You can follow the conversation and add further details from your portal.
</p>
@endcomponent
