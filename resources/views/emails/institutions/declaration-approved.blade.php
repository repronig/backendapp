@component('emails.layouts.app')
@slot('subject') Annual Declaration Approved @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $declaration->institution->contact_person_name ?: $declaration->institution->name }},</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Your annual declaration for {{ $declaration->licensing_year }} has been approved.
</p>
<p style="margin:0 0 20px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    An invoice has been generated for this declaration and is now due in your institution account. You can review the invoice and proceed with payment from your invoices page.
</p>
<div style="margin:0 0 24px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Declaration Year:</strong> {{ $declaration->licensing_year }}<br>
    @if($declaration->invoice)
        <strong>Invoice Number:</strong> {{ $declaration->invoice->invoice_number }}<br>
        <strong>Amount Due:</strong> {{ strtoupper((string) $declaration->invoice->currency) === 'NGN' ? '₦' : $declaration->invoice->currency . ' ' }}{{ number_format($declaration->invoice->total_amount, 2) }}<br>
        <strong>Due Date:</strong> {{ optional($declaration->invoice->due_date)->format('Y-m-d') }}
    @else
        <strong>Invoice:</strong> Available in your institution account.
    @endif
</div>
<p style="margin:0 0 18px; text-align:center;">
    <a href="{{ $invoiceUrl }}" style="display:inline-block; background:#f4c430; color:#7a1018; text-decoration:none; font-weight:bold; border-radius:12px; padding:14px 24px;">
        Check your invoice
    </a>
</p>
<p style="margin:0; font-size:14px; line-height:1.6; color:#6b6b6b;">
    If you are not already signed in, the platform will ask you to log in before showing your invoice list.
</p>
@endcomponent
