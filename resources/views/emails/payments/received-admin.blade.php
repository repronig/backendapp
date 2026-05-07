@component('emails.layouts.app')
@slot('subject') Institution licence payment received @endslot
@php
    $institution = $payment->institution;
    $invoice = $payment->invoice;
    $currencyPrefix = strtoupper((string) $payment->currency) === 'NGN' ? '₦' : $payment->currency.' ';
    $amountPaid = (float) ($payment->amount_allocated ?: $payment->amount);
    $outstanding = $invoice ? (float) $invoice->outstanding_amount : (float) ($payment->balance_after ?? 0);
    $isFullyPaid = $outstanding <= 0;
@endphp
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello,</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    <strong>{{ $institution?->name ?? 'An institution' }}</strong> completed an online licence payment.
</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Payment reference:</strong> {{ $payment->payment_reference }}<br>
    @if($invoice)<strong>Invoice:</strong> {{ $invoice->invoice_number }}<br>@endif
    <strong>Amount recorded:</strong> {{ $currencyPrefix }}{{ number_format($amountPaid, 2) }}<br>
    <strong>Invoice status:</strong> {{ $isFullyPaid ? 'Paid in full' : 'Part payment (balance remains)' }}
</div>
<p style="margin:0 0 18px; text-align:center;">
    <a href="{{ $adminFinanceUrl }}" style="display:inline-block; background:#b01217; color:#ffffff; text-decoration:none; font-weight:bold; border-radius:12px; padding:14px 24px;">
        Open admin finance
    </a>
</p>
<p style="margin:0; font-size:14px; line-height:1.6; color:#6b6b6b;">
    The institution contact has been emailed with a PDF receipt{{ $isFullyPaid ? ' and licence certificate (when applicable)' : '' }}.
</p>
@endcomponent
