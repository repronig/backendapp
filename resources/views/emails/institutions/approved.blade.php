@component('emails.layouts.app')
@slot('subject') Institution Registration Approved @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $institution->contact_person_name ?: $institution->name }},</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Your institution registration has been approved on REPRONIG.
</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Institution:</strong> {{ $institution->name }}<br>
    <strong>Licence ID:</strong> {{ $institution->licence_id ?: 'Will be assigned on licensing' }}
</div>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    You can now continue with declarations and licensing activities.
</p>
@endcomponent
