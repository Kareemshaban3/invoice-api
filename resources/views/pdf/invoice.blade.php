<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style>
        body {
            font-family: cairo, sans-serif;
            font-size: 12px;
            color: #222;
        }

        .rtl { direction: rtl; text-align: right; unicode-bidi: embed; }
        .ltr { direction: ltr; text-align: left; unicode-bidi: embed; }

        .header-table,
        .info-table,
        .items-table,
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td,
        .info-table td {
            vertical-align: top;
        }

        .section {
            border: 1px solid #e5e5e5;
            padding: 12px;
            margin-bottom: 14px;
            border-radius: 8px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 6px 0;
        }

        .subtitle {
            font-size: 13px;
            color: #666;
            margin: 0;
        }

        .label {
            font-weight: bold;
            color: #111;
        }

        .muted {
            color: #666;
        }

        .items-table {
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #dcdcdc;
            padding: 8px;
            font-size: 11px;
        }

        .items-table th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .totals-table {
            margin-top: 14px;
        }

        .totals-table td {
            border: 1px solid #dcdcdc;
            padding: 8px;
        }

        .totals-table .label-cell {
            width: 25%;
            background: #fafafa;
            font-weight: bold;
        }

        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }

        .mb-0 { margin-bottom: 0; }
        .mt-10 { margin-top: 10px; }

        .logo {
            max-width: 90px;
            max-height: 90px;
        }
    </style>
</head>

@php
    $isArabic = app()->getLocale() === 'ar';

    $currencyLabel = $invoice->currency?->code
        ?? $invoice->currency?->name
        ?? '-';

    $remainingAmount = max(0, (float) $invoice->total - (float) $invoice->paid);

    $invoiceDate = $invoice->date ? \Illuminate\Support\Carbon::parse($invoice->date)->format('Y-m-d') : '-';
    $dueDate = $invoice->due_date ? \Illuminate\Support\Carbon::parse($invoice->due_date)->format('Y-m-d') : '-';
@endphp

<body class="{{ $isArabic ? 'rtl' : 'ltr' }}">

    {{-- Header --}}
    <table class="header-table" style="margin-bottom: 16px;">
        <tr>
            <td style="width: 50%; {{ $isArabic ? 'text-align:right;' : 'text-align:left;' }}">
                @if (!empty($settings?->logo_path))
                    <img
                        src="{{ public_path('storage/' . $settings->logo_path) }}"
                        class="logo"
                        alt="Logo"
                    >
                @endif
            </td>

            <td style="width: 50%; {{ $isArabic ? 'text-align:left;' : 'text-align:right;' }}">
                @if (!empty($settings?->site_name))
                    <div class="title">{{ $settings->site_name }}</div>
                @else
                    <div class="title">{{ $isArabic ? 'فاتورة' : 'Invoice' }}</div>
                @endif

                @if (!empty($settings?->site_url))
                    <div class="subtitle">{{ $settings->site_url }}</div>
                @endif

                @if (!empty($settings?->site_email))
                    <div class="subtitle">{{ $settings->site_email }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Invoice info --}}
    <div class="section">
        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <div class="title mb-0">
                        {{ $isArabic ? 'فاتورة' : 'Invoice' }}
                        #{{ $invoice->number }}
                    </div>
                </td>

                <td style="width: 50%; {{ $isArabic ? 'text-align:right;' : 'text-align:left;' }}">
                    <div style="margin-bottom: 6px;">
                        <span class="label">{{ $isArabic ? 'تاريخ الإصدار:' : 'Issue Date:' }}</span>
                        {{ $invoiceDate }}
                    </div>

                    <div style="margin-bottom: 6px;">
                        <span class="label">{{ $isArabic ? 'تاريخ الاستحقاق:' : 'Due Date:' }}</span>
                        {{ $dueDate }}
                    </div>

                    <div>
                        <span class="label">{{ $isArabic ? 'العملة:' : 'Currency:' }}</span>
                        {{ $currencyLabel }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Client info --}}
    <div class="section">
        <div class="title" style="font-size: 18px;">
            {{ $isArabic ? 'بيانات العميل' : 'Client Information' }}
        </div>

        <div><strong>{{ $invoice->client->name ?? '-' }}</strong></div>
        @if(!empty($invoice->client->phone))
            <div>{{ $invoice->client->phone }}</div>
        @endif
        @if(!empty($invoice->client->email))
            <div>{{ $invoice->client->email }}</div>
        @endif
        @if(!empty($invoice->client->country))
            <div>{{ $invoice->client->country }}</div>
        @endif
        @if(!empty($invoice->client->address))
            <div>{{ $invoice->client->address }}</div>
        @endif
    </div>

    {{-- Items --}}
    <div class="section">
        <div class="title" style="font-size: 18px;">
            {{ $isArabic ? 'بنود الفاتورة' : 'Invoice Items' }}
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 6%;">#</th>
                    <th style="width: 32%;">{{ $isArabic ? 'الصنف' : 'Item' }}</th>
                    <th style="width: 14%;">{{ $isArabic ? 'النوع' : 'Type' }}</th>
                    <th style="width: 14%;">{{ $isArabic ? 'السعر' : 'Price' }}</th>
                    <th style="width: 10%;">{{ $isArabic ? 'الكمية' : 'Qty' }}</th>
                    <th style="width: 12%;">{{ $isArabic ? 'الخصم' : 'Discount' }}</th>
                    <th style="width: 12%;">{{ $isArabic ? 'الإجمالي' : 'Total' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoice->items as $idx => $it)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $it->product_name ?: ($isArabic ? 'خدمة' : 'Service') }}</strong>
                            @if(!empty($it->product_description))
                                <div class="muted">{{ $it->product_description }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $it->item_type === 'service' ? ($isArabic ? 'خدمة' : 'Service') : ($isArabic ? 'منتج' : 'Product') }}
                        </td>
                        <td class="text-center">
                            {{ number_format((float) $it->unit_price, 2) }} {{ $currencyLabel }}
                        </td>
                        <td class="text-center">
                            {{ number_format((float) $it->quantity, 2) }}
                        </td>
                        <td class="text-center">
                            @if(($it->discount_type ?? 'none') === 'percent')
                                {{ number_format((float) ($it->discount_value ?? 0), 2) }}%
                            @elseif(($it->discount_type ?? 'none') === 'amount')
                                {{ number_format((float) ($it->discount_value ?? 0), 2) }} {{ $currencyLabel }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">
                            {{ number_format((float) $it->line_total, 2) }} {{ $currencyLabel }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">
                            {{ $isArabic ? 'لا توجد بنود' : 'No items found' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Totals --}}
        <table class="totals-table">
            <tbody>
                <tr>
                    <td class="label-cell">{{ $isArabic ? 'المجموع الفرعي' : 'Subtotal' }}</td>
                    <td class="{{ $isArabic ? 'text-left' : 'text-right' }}">
                        {{ number_format((float) $invoice->subtotal, 2) }} {{ $currencyLabel }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">{{ $isArabic ? 'الخصم' : 'Discount' }}</td>
                    <td class="{{ $isArabic ? 'text-left' : 'text-right' }}">
                        {{ number_format((float) $invoice->discount, 2) }} {{ $currencyLabel }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">{{ $isArabic ? 'الضريبة' : 'Tax' }}</td>
                    <td class="{{ $isArabic ? 'text-left' : 'text-right' }}">
                        {{ number_format((float) $invoice->tax_total, 2) }} {{ $currencyLabel }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">{{ $isArabic ? 'الإجمالي النهائي' : 'Grand Total' }}</td>
                    <td class="{{ $isArabic ? 'text-left' : 'text-right' }}">
                        <strong>{{ number_format((float) $invoice->total, 2) }} {{ $currencyLabel }}</strong>
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">{{ $isArabic ? 'المدفوع' : 'Paid' }}</td>
                    <td class="{{ $isArabic ? 'text-left' : 'text-right' }}">
                        {{ number_format((float) $invoice->paid, 2) }} {{ $currencyLabel }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">{{ $isArabic ? 'المتبقي' : 'Remaining' }}</td>
                    <td class="{{ $isArabic ? 'text-left' : 'text-right' }}">
                        <strong>{{ number_format($remainingAmount, 2) }} {{ $currencyLabel }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>

        @if ($invoice->notes)
            <div class="mt-10">
                <strong>{{ $isArabic ? 'ملاحظات:' : 'Notes:' }}</strong>
                {{ $invoice->notes }}
            </div>
        @endif

        @if (!empty($settings?->invoice_footer))
            <div style="margin-top: 16px; color:#666; font-size:11px;">
                {!! $settings->invoice_footer !!}
            </div>
        @endif
    </div>

</body>
</html>