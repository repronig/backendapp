@component('emails.layouts.app')
@slot('subject') New support ticket {{ \App\Models\SupportTicket::formattedReference((int) ($ticket?->id ?? 0)) }} @endslot
<p style="margin:0 0 12px; font-size:15px; line-height:1.6; color:#667085;">Ticket <strong style="color:#101828;">{{ \App\Models\SupportTicket::formattedReference((int) ($ticket?->id ?? 0)) }}</strong></p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello,</p>
<p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    A new support ticket was opened
    @if($ticket?->user?->name)
        by <strong>{{ $ticket->user->name }}</strong>
    @endif
    @if($ticket?->subject)
        with subject <strong>“{{ $ticket->subject }}”</strong>.
    @else
        .
    @endif
</p>
<p style="margin:0 0 24px; font-size:16px; line-height:1.7;">
    <a href="{{ $ticketUrl }}" style="display:inline-block; padding:12px 18px; background:#b01217; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:700;">Review ticket</a>
</p>
@endcomponent
