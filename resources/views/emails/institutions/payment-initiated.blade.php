@component('emails.layouts.app')
@slot('subject') Payment initiated @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello,</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    A licence payment has been initiated for your institution account.
</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Amount:</strong> {{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}<br>
    @if($payment->payment_reference)
        <strong>Payment reference:</strong> {{ $payment->payment_reference }}<br>
    @endif
    @if($licence && ($licence->licence_number ?? null))
        <strong>Licence:</strong> {{ $licence->licence_number }}<br>
    @endif
    <strong>Gateway:</strong> {{ ucfirst((string) $payment->gateway_name) }}
</div>
@if($payment->raw_response_json && !empty($payment->raw_response_json['authorization_url']))
<p style="margin:0 0 18px; text-align:center;">
    <a href="{{ $payment->raw_response_json['authorization_url'] }}" style="display:inline-block; background:#f4c430; color:#7a1018; text-decoration:none; font-weight:bold; border-radius:12px; padding:14px 24px;">
        Complete payment
    </a>
</p>
@endif
<p style="margin:0 0 18px; text-align:center;">
    <a href="{{ $licencesUrl }}" style="display:inline-block; border:1px solid #7a1018; color:#7a1018; text-decoration:none; font-weight:bold; border-radius:12px; padding:14px 24px;">
        Open licences
    </a>
</p>
<p style="margin:0; font-size:14px; line-height:1.6; color:#6b6b6b;">
    If you did not initiate this payment, secure your account and contact support.
</p>
@endcomponent
