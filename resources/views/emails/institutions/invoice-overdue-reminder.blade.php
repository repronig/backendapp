@component('emails.layouts.app')
@slot('subject') Invoice Overdue Reminder @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $invoice->institution->contact_person_name ?: $invoice->institution->name }},</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Invoice <strong>{{ $invoice->invoice_number }}</strong> is now overdue.
</p>
<div style="margin:0 0 22px; background:#fff4f4; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#8f171c;">
    <strong>Outstanding:</strong> {{ strtoupper((string) $invoice->currency) === 'NGN' ? '₦' : $invoice->currency . ' ' }}{{ number_format($invoice->outstanding_amount, 2) }}
</div>
@endcomponent
