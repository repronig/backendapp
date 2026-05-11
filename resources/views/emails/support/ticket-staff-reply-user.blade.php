@component('emails.layouts.app')
@slot('subject') Reply on your ticket {{ \App\Models\SupportTicket::formattedReference((int) ($ticket?->id ?? 0)) }} @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $ticket?->user?->name ?? 'there' }},</p>
<p style="margin:0 0 12px; font-size:15px; line-height:1.6; color:#667085;">Ticket reference: <strong style="color:#101828;">{{ \App\Models\SupportTicket::formattedReference((int) ($ticket?->id ?? 0)) }}</strong></p>
<p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Our support team has posted a reply regarding your ticket
    @if($ticket?->subject)
        <strong>“{{ $ticket->subject }}”</strong>.
    @else
        .
    @endif
</p>
@if($reply?->body)
<p style="margin:0 0 18px; font-size:15px; line-height:1.6; color:#2b2b2b; padding:14px 16px; background:#f9fafb; border-radius:8px; border:1px solid #eaecf0;">
    {!! nl2br(e($reply->body)) !!}
</p>
@endif
<p style="margin:0 0 24px; font-size:16px; line-height:1.7;">
    <a href="{{ $ticketUrl }}" style="display:inline-block; padding:12px 18px; background:#b01217; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:700;">Open ticket</a>
</p>
@endcomponent
