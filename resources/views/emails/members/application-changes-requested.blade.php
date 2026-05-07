@component('emails.layouts.app')
@slot('subject') Changes Requested on Your Application @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $user->first_name ?? 'there' }},</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Your REPRONIG membership application has been reviewed, and some changes or additional clarification are required before it can proceed.
</p>
<p style="margin:0 0 10px; font-size:16px; line-height:1.7; color:#4c4c4c;">Comment:</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    {{ $comment }}
</div>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Please log in to your account, update the requested information, and resubmit your application.
</p>
@endcomponent
