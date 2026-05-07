@component('emails.layouts.app')
@slot('subject') Licence Payment Initiated @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $user->first_name ?? 'there' }},</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    A payment has been initiated for your REPRONIG licence.
</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Licence Year:</strong> {{ $licence->licence_year }}<br>
    <strong>Licence Number:</strong> {{ $licence->licence_number }}<br>
    <strong>Payment Reference:</strong> {{ $payment->payment_reference }}<br>
    <strong>Amount:</strong> ₦{{ number_format((float) $payment->amount, 2) }}
</div>
@if($paymentUrl)
    <p style="margin:28px 0 0;">
        <a href="{{ $paymentUrl }}" style="display:inline-block; background:#b01217; color:#ffffff; text-decoration:none; padding:15px 28px; border-radius:12px; font-size:16px; font-weight:700;">
            Complete Payment
        </a>
    </p>
@endif
@endcomponent
