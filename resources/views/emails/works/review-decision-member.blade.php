@component('emails.layouts.app')
@slot('subject') Work review update @endslot
@php
    $title = $work->title ?: 'your work';
@endphp
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello,</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    @if($decision === 'verified')
        Your work <strong>“{{ $title }}”</strong> has been verified.
    @elseif($decision === 'approved')
        Your work <strong>“{{ $title }}”</strong> has been approved.
    @elseif($decision === 'rejected')
        Your work <strong>“{{ $title }}”</strong> was rejected.
    @elseif($decision === 'changes_requested')
        Changes were requested for your work <strong>“{{ $title }}”</strong>.
    @else
        Your work <strong>“{{ $title }}”</strong> has a new review update.
    @endif
</p>

@if($reviewNote)
    <div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
        <strong>Reviewer note:</strong><br>
        {{ $reviewNote }}
    </div>
@endif

<p style="margin:0 0 18px; text-align:center;">
    <a href="{{ $memberWorksUrl }}" style="display:inline-block; background:#b01217; color:#ffffff; text-decoration:none; font-weight:bold; border-radius:12px; padding:14px 24px;">
        View your works
    </a>
</p>
@endcomponent
