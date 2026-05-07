@component('emails.layouts.app')
@slot('subject') Offline Licence Payment Rejected @endslot
@php
    $institution = $payment->institution;
    $currencyPrefix = strtoupper((string) $payment->currency) === 'NGN' ? '₦' : $payment->currency . ' ';
    $amount = (float) ($payment->amount_allocated ?: $payment->amount);
@endphp
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Hello {{ $institution->contact_person_name ?: $institution->name }},
</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Your offline licence payment has been rejected.
</p>

<div style="margin:0 0 22px; background:#fff4f4; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#8f171c;">
    <strong>Payment Reference:</strong> {{ $payment->payment_reference }}<br>
    <strong>Amount:</strong> {{ $currencyPrefix }}{{ number_format($amount, 2) }}<br>
    @if($payment->invoice)
        <strong>Invoice Number:</strong> {{ $payment->invoice->invoice_number }}<br>
    @endif
    @if($payment->licence)
        <strong>Licence:</strong> {{ $payment->licence->licence_number ?: 'Pending final issuance' }}<br>
    @endif
</div>

@if($reason)
    <div style="margin:0 0 22px; background:#fff4f4; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#8f171c;">
        <strong>Rejection Reason:</strong><br>{{ $reason }}
    </div>
@endif

<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    If you believe this decision was made in error, please contact REPRONIG support using the email shown in your invitation or profile.
</p>
@endcomponent

