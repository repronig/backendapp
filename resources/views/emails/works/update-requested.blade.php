@component('emails.layouts.app')
@slot('subject') Work Update Request Submitted @endslot
    <p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello Admin,</p>
    <p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">A member has requested permission to edit an approved work.</p>
    <p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">
        <strong>Work Title:</strong> {{ $work->title }}<br>
        <strong>Reference:</strong> {{ $work->reference_number ?? ('WORK-'.$work->id) }}<br>
        <strong>Member:</strong> {{ optional(optional($work->member)->user)->name ?? 'Unknown member' }}<br>
        <strong>Request Note:</strong> {{ $work->update_request_note ?: '—' }}
    </p>
    <p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">Please review this request from the admin work review dashboard.</p>
@endcomponent
