@component('emails.layouts.app')
@slot('subject') Institution annual declaration submitted @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello,</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    <strong>{{ $institutionDisplayName }}</strong> has submitted an annual licensing declaration for year <strong>{{ $licensingYearDisplay }}</strong>.
</p>
<p style="margin:0 0 24px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Please review the declaration in the admin portal and move it to review or take the next licensing step when ready.
</p>
<p style="margin:0 0 18px; text-align:center;">
    <a href="{{ $adminDeclarationsUrl }}" style="display:inline-block; background:#b01217; color:#ffffff; text-decoration:none; font-weight:bold; border-radius:12px; padding:14px 24px;">
        Open declarations
    </a>
</p>
<p style="margin:0; font-size:14px; line-height:1.6; color:#6b6b6b;">
    If you are not already signed in, the platform will ask you to log in before showing the declarations list.
</p>
@endcomponent
