@component('emails.layouts.app')
@slot('subject') Payment Confirmed @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $user->first_name ?? 'there' }},</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Your licence payment has been successfully confirmed.
</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Licence Year:</strong> {{ $licence->licence_year }}<br>
    <strong>Licence Number:</strong> {{ $licence->licence_number }}<br>
    <strong>Payment Reference:</strong> {{ $payment->payment_reference }}<br>
    <strong>Amount Paid:</strong> ₦{{ number_format((float) $payment->amount, 2) }}
</div>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Thank you. Your payment has been recorded successfully.
</p>
@endcomponent
