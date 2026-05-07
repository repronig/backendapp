@component('emails.layouts.app')
@slot('subject') REPRONIG Payment Receipt @endslot
@php
    $institution = $payment->institution;
    $invoice = $payment->invoice;
    $licence = $payment->licence;
    $declaration = $payment->declaration;
    $currencyPrefix = strtoupper((string) $payment->currency) === 'NGN' ? '₦' : $payment->currency . ' ';
    $amountPaid = (float) ($payment->amount_allocated ?: $payment->amount);
    $outstanding = $invoice ? (float) $invoice->outstanding_amount : (float) ($payment->balance_after ?? 0);
    $isFullyPaid = $outstanding <= 0;
@endphp
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $institution->contact_person_name ?: $institution->name }},</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Thank you for your payment to REPRONIG. Your payment has been received and recorded successfully.</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Payment Reference:</strong> {{ $payment->payment_reference }}<br>
    @if($invoice)<strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>@endif
    @if($declaration)<strong>Declaration Year:</strong> {{ $declaration->licensing_year }}<br>@endif
    <strong>Amount Paid:</strong> {{ $currencyPrefix }}{{ number_format($amountPaid, 2) }}<br>
    <strong>Outstanding Balance:</strong> {{ $currencyPrefix }}{{ number_format(max($outstanding, 0), 2) }}<br>
    <strong>Payment Status:</strong> {{ $isFullyPaid ? 'Paid in full' : 'Part payment received' }}
</div>
@if($isFullyPaid)
    <p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">Your payment has been made in full.</p>
    @if($licence)
        <div style="margin:0 0 22px; background:#fff4f4; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#8f171c;">
            <strong>Licence Number:</strong> {{ $licence->licence_number ?: 'Pending final issuance' }}<br>
            <strong>Licence Year:</strong> {{ $licence->licence_year }}<br>
            <strong>Licence Status:</strong> {{ ucwords(str_replace('_', ' ', (string) $licence->licence_status)) }}<br>
            @if($licence->start_date)<strong>Start Date:</strong> {{ $licence->start_date->format('Y-m-d') }}<br>@endif
            @if($licence->end_date)<strong>End Date:</strong> {{ $licence->end_date->format('Y-m-d') }}@endif
        </div>
    @endif
    <p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">Thank you for completing your licence payment obligation.</p>
@else
    <p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">There is still an outstanding balance on this invoice. Please complete the outstanding payment as soon as possible so that your licence can be approved.</p>
    <div style="margin:0 0 22px; background:#fff4f4; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#8f171c;"><strong>Outstanding Amount:</strong> {{ $currencyPrefix }}{{ number_format(max($outstanding, 0), 2) }}</div>
@endif
@endcomponent
