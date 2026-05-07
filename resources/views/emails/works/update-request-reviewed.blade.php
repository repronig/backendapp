@component('emails.layouts.app')
@slot('subject')
    @if(($decision ?? '') === 'approved')
        Work Update Request Approved
    @else
        Work Update Request Rejected
    @endif
@endslot
    <p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ optional(optional($work->member)->user)->name ?? 'Member' }},</p>
    <p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
        Your request to update the approved work <strong>{{ $work->title }}</strong>
        has been <strong>{{ $decision }}</strong>.
    </p>
    @if(($decision ?? '') === 'approved')
        <p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">You can now edit the work and submit it again for review.</p>
    @else
        <p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">You cannot edit this work at the moment. Please contact support if you need further clarification.</p>
    @endif
    <p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
        <strong>Work Reference:</strong> {{ $work->reference_number ?? ('WORK-'.$work->id) }}<br>
        <strong>Review Note:</strong> {{ $work->update_request_review_note ?: '—' }}
    </p>
@endcomponent
