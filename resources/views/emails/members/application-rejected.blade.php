@component('emails.layouts.app')
@slot('subject') Update on Your Application @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $user->first_name ?? 'there' }},</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    We regret to inform you that your REPRONIG membership application was not approved at this time.
</p>
<p style="margin:0 0 10px; font-size:16px; line-height:1.7; color:#4c4c4c;">Reason provided:</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    {{ $reason }}
</div>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    You may review the feedback and contact support or your relevant association if further clarification is required.
</p>
@endcomponent
