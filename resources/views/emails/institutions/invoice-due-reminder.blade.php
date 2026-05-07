@component('emails.layouts.app')
@slot('subject') Invoice Due Reminder @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $invoice->institution->contact_person_name ?: $invoice->institution->name }},</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    This is a reminder that invoice <strong>{{ $invoice->invoice_number }}</strong> is due on {{ optional($invoice->due_date)->format('Y-m-d') }}.
</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Outstanding:</strong> {{ strtoupper((string) $invoice->currency) === 'NGN' ? '₦' : $invoice->currency . ' ' }}{{ number_format($invoice->outstanding_amount, 2) }}
</div>
@endcomponent
