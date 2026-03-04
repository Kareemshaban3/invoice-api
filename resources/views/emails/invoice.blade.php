<!doctype html>
<html lang="{{ app()->getLocale() }}">
<body style="font-family: Arial, sans-serif;">
  <h2>
    {{ app()->getLocale()==='ar' ? 'تم إرسال الفاتورة' : 'Invoice Sent' }}
  </h2>

  <p>
    {{ app()->getLocale()==='ar'
        ? 'مرفق مع هذه الرسالة ملف الفاتورة بصيغة PDF.'
        : 'Attached is your invoice as a PDF file.' }}
  </p>

  <p>
    <strong>#{{ $invoice->number }}</strong><br>
    {{ app()->getLocale()==='ar' ? 'الإجمالي' : 'Total' }}:
    {{ number_format((float)$invoice->total, 2) }} {{ $invoice->currency }}<br>
    {{ app()->getLocale()==='ar' ? 'المستحق' : 'Due' }}:
    {{ number_format((float)$invoice->due, 2) }} {{ $invoice->currency }}
  </p>

  <p style="color:#666;">
    {{ app()->getLocale()==='ar' ? 'شكراً لك.' : 'Thank you.' }}
  </p>
</body>
</html>
