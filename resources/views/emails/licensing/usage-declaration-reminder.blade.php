@component('emails.layouts.app')
@slot('subject') Usage Declaration Reminder @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $user->first_name ?? 'there' }},</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    This is a reminder to submit your annual usage declaration for your REPRONIG licence.
</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Licence Year:</strong> {{ $licence->licence_year }}<br>
    <strong>Licence Number:</strong> {{ $licence->licence_number }}
</div>
@if($declarationUrl)
    <p style="margin:28px 0 0;">
        <a href="{{ $declarationUrl }}" style="display:inline-block; background:#b01217; color:#ffffff; text-decoration:none; padding:15px 28px; border-radius:12px; font-size:16px; font-weight:700;">
            Submit Usage Declaration
        </a>
    </p>
@endif
<p style="margin:22px 0 0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Please complete this requirement before the applicable deadline to keep your records up to date.
</p>
@endcomponent
