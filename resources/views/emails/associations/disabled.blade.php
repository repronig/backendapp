@component('emails.layouts.app')
@slot('subject') Association Disabled @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $association->name }},</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Your association has been disabled on the REPRONIG platform.
</p>
@if($association->disable_reason)
    <div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:16px 18px; font-size:16px; line-height:1.7; color:#3d3d3d;">
        <strong>Reason:</strong> {{ $association->disable_reason }}
    </div>
@endif
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Please contact the platform administrator for assistance.
</p>
@endcomponent
