@component('emails.layouts.app')
@slot('subject') Association Access Restored @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Hello {{ $association->name }},
</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Your association access for <strong>{{ $association->name }}</strong> has been restored on REPRONIG.
</p>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    You may continue your activities using the platform.
</p>
@endcomponent

