<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style>
        body { font-family: cairo, sans-serif; font-size: 12px; }
        .rtl { direction: rtl; text-align: right; unicode-bidi: embed; }
        .ltr { direction: ltr; text-align: left; unicode-bidi: embed; }

        .box { border: 1px solid #eee; padding: 10px; border-radius: 6px; margin-bottom: 10px; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f5f5f5; }

        .muted { color: #666; }
        .right { text-align: right; }
        .left { text-align: left; }
    </style>
</head>

@php
    $isArabic = app()->getLocale() === 'ar';
@endphp

<body class="{{ $isArabic ? 'rtl' : 'ltr' }}">

    {{-- HEADER: Logo + Site name (top right in Arabic) --}}
    <div style="width:100%; margin-bottom: 12px;">
        <div style="float: {{ $isArabic ? 'right' : 'left' }}; text-align: {{ $isArabic ? 'right' : 'left' }};">

            @if (!empty($settings?->logo_path))
                <img
                    src="{{ public_path('storage/' . $settings->logo_path) }}"
                    style="width: 80px; height: auto; display:block; margin-bottom: 6px;"
                >
            @endif

            @if (!empty($settings?->site_name))
                <div style="font-weight:bold; font-size:14px;">{{ $settings->site_name }}</div>
            @endif

            {{-- اختياري: رابط/ايميل --}}
            @if (!empty($settings?->site_url))
                <div style="font-size:11px; color:#666;">{{ $settings->site_url }}</div>
            @endif
            @if (!empty($settings?->site_email))
                <div style="font-size:11px; color:#666;">{{ $settings->site_email }}</div>
            @endif
        </div>

        <div style="clear: both;"></div>
    </div>

    <div class="box">
        <h2 style="margin:0;">
            {{ $isArabic ? 'فاتورة' : 'Invoice' }}
            <span class="muted">#{{ $invoice->number }}</span>
        </h2>

        <p style="margin:6px 0 0;">
            <strong>{{ $isArabic ? 'تاريخ الإصدار' : 'Date' }}:</strong> {{ $invoice->date }} <br>
            <strong>{{ $isArabic ? 'تاريخ الاستحقاق' : 'Due Date' }}:</strong> {{ $invoice->due_date ?? '-' }} <br>
            <strong>{{ $isArabic ? 'العملة' : 'Currency' }}:</strong> {{ $invoice->currency }}
        </p>
    </div>

    <div class="box">
        <h3 style="margin:0 0 8px;">
            {{ $isArabic ? 'بيانات العميل' : 'Client' }}
        </h3>
        <p style="margin:0;">
            <strong>{{ $invoice->client->name ?? '-' }}</strong><br>
            {{ $invoice->client->phone ?? '' }}<br>
            {{ $invoice->client->email ?? '' }}<br>
            {{ $invoice->client->country ?? '' }}
        </p>
    </div>

    <div class="box">
        <h3 style="margin:0 0 8px;">
            {{ $isArabic ? 'بنود الفاتورة' : 'Invoice Items' }}
        </h3>

        <table>
            <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th>{{ $isArabic ? 'المنتج' : 'Product' }}</th>
                <th style="width:90px;">{{ $isArabic ? 'السعر' : 'Price' }}</th>
                <th style="width:90px;">{{ $isArabic ? 'الكمية' : 'Qty' }}</th>
                <th style="width:110px;">{{ $isArabic ? 'الإجمالي' : 'Total' }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($invoice->items as $idx => $it)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $it->product_name }}</td>
                    <td>{{ number_format((float) $it->unit_price, 2) }} {{ $invoice->currency }}</td>
                    <td>{{ number_format((float) $it->quantity, 2) }}</td>
                    <td>{{ number_format((float) $it->line_total, 2) }} {{ $invoice->currency }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <table style="margin-top:12px;">
            <tbody>
            <tr>
                <td><strong>{{ $isArabic ? 'المجموع' : 'Subtotal' }}</strong></td>
                <td class="{{ $isArabic ? 'left' : 'right' }}">
                    {{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}
                </td>
            </tr>
            <tr>
                <td><strong>{{ $isArabic ? 'الخصم' : 'Discount' }}</strong></td>
                <td class="{{ $isArabic ? 'left' : 'right' }}">
                    {{ number_format((float) $invoice->discount, 2) }} {{ $invoice->currency }}
                </td>
            </tr>
            <tr>
                <td><strong>{{ $isArabic ? 'الإجمالي' : 'Total' }}</strong></td>
                <td class="{{ $isArabic ? 'left' : 'right' }}">
                    <strong>{{ number_format((float) $invoice->total, 2) }} {{ $invoice->currency }}</strong>
                </td>
            </tr>
            <tr>
                <td><strong>{{ $isArabic ? 'المدفوع' : 'Paid' }}</strong></td>
                <td class="{{ $isArabic ? 'left' : 'right' }}">
                    {{ number_format((float) $invoice->paid, 2) }} {{ $invoice->currency }}
                </td>
            </tr>
            <tr>
                <td><strong>{{ $isArabic ? 'المستحق' : 'Due' }}</strong></td>
                <td class="{{ $isArabic ? 'left' : 'right' }}">
                    {{ number_format((float) $invoice->due, 2) }} {{ $invoice->currency }}
                </td>
            </tr>
            </tbody>
        </table>

        @if ($invoice->notes)
            <p style="margin-top:10px;">
                <strong>{{ $isArabic ? 'ملاحظات' : 'Notes' }}:</strong>
                {{ $invoice->notes }}
            </p>
        @endif

        {{-- اختياري: Footer settings --}}
        @if (!empty($settings?->invoice_footer))
            <div style="margin-top: 12px; color:#666; font-size:11px;">
                {!! $settings->invoice_footer !!}
            </div>
        @endif
    </div>

</body>
</html>
