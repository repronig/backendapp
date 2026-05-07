@component('emails.layouts.app')
@slot('subject') New work submitted for review @endslot
@php
    $resolvedMemberName = $memberName ?? optional(optional($work ?? null)->member)->user->name ?? 'A member';
    $resolvedWorkTitle = $workTitle ?? (($work->title ?? null) ?: 'Untitled work');
    $resolvedWorkReference = $workReference ?? (($work->reference_number ?? null) ?: (($work->identifier_value ?? null) ?: 'N/A'));
    $resolvedWorkStatus = $workStatus ?? (($work->work_status instanceof \BackedEnum) ? $work->work_status->value : (string) ($work->work_status ?? 'unknown'));
    $resolvedVerificationStatus = $verificationStatus ?? (($work->verification_status instanceof \BackedEnum) ? $work->verification_status->value : (string) ($work->verification_status ?? 'unknown'));
@endphp
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello,</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    {{ $resolvedMemberName }} submitted a new work for verification and approval.
</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Title:</strong> {{ $resolvedWorkTitle }}<br>
    <strong>Reference:</strong> {{ $resolvedWorkReference }}<br>
    <strong>Status:</strong> {{ $resolvedWorkStatus }} / {{ $resolvedVerificationStatus }}
</div>
<p style="margin:0 0 18px; text-align:center;">
    <a href="{{ $adminWorksUrl }}" style="display:inline-block; background:#b01217; color:#ffffff; text-decoration:none; font-weight:bold; border-radius:12px; padding:14px 24px;">
        Open admin works
    </a>
</p>
@endcomponent
