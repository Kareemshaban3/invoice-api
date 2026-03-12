<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style>
        body {
            font-family: cairo, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.9;
        }

        .rtl {
            direction: rtl;
            text-align: right;
            unicode-bidi: embed;
        }

        .ltr {
            direction: ltr;
            text-align: left;
            unicode-bidi: embed;
        }

        .header-table,
        .info-table,
        .items-table,
        .totals-table,
        .two-col-table,
        .company-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td,
        .info-table td,
        .two-col-table td,
        .company-table td {
            vertical-align: top;
        }

        .section {
            border: 1px solid #e5e7eb;
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 10px;
            background: #ffffff;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin: 0 0 8px 0;
            color: #111827;
        }

        .section-title {
            font-size: 17px;
            font-weight: bold;
            margin: 0 0 12px 0;
            color: #111827;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }

        .subtitle {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 4px 0;
        }

        .label {
            font-weight: bold;
            color: #111827;
        }

        .muted {
            color: #6b7280;
        }

        .logo {
            max-width: 90px;
            max-height: 90px;
        }

        .invoice-badge {
            display: inline-block;
            padding: 4px 10px;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            font-size: 11px;
            color: #374151;
            background: #f9fafb;
        }

        .items-table {
            margin-top: 10px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #d1d5db;
            padding: 9px;
            font-size: 11px;
        }

        .items-table th {
            background: #f3f4f6;
            font-weight: bold;
            color: #111827;
        }

        .items-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .totals-table {
            margin-top: 16px;
        }

        .totals-table td {
            border: 1px solid #d1d5db;
            padding: 9px;
        }

        .totals-table .label-cell {
            width: 35%;
            background: #f9fafb;
            font-weight: bold;
        }

        .grand-total-row td {
            background: #f3f4f6;
            font-size: 13px;
            font-weight: bold;
        }

        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }

        .mb-0 { margin-bottom: 0; }
        .mb-4 { margin-bottom: 4px; }
        .mb-6 { margin-bottom: 6px; }
        .mb-8 { margin-bottom: 8px; }
        .mt-10 { margin-top: 10px; }
        .mt-14 { margin-top: 14px; }

        .small {
            font-size: 11px;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }

        .company-meta {
            font-size: 11px;
            color: #4b5563;
            margin-bottom: 3px;
        }

        .notes-box {
            border: 1px dashed #d1d5db;
            background: #fcfcfc;
            padding: 10px;
            border-radius: 8px;
            margin-top: 12px;
        }

        .footer-box {
            margin-top: 16px;
            color: #6b7280;
            font-size: 11px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .info-line {
            margin-bottom: 8px;
            padding-bottom: 0;
            border-bottom: none;
            line-height: 1.9;
        }

        .info-line:last-child {
            margin-bottom: 0;
        }

        .summary-box,
        .party-box {
            padding: 14px 16px;
        }

        .value-block {
            display: inline-block;
            margin-right: 4px;
            margin-left: 4px;
        }
    </style>
</head>

@php
    use Illuminate\Support\Carbon;

    $isArabic = app()->getLocale() === 'ar';

    $currencyLabel = $invoice->currency?->code
        ?? $invoice->currency?->name
        ?? '-';

    $remainingAmount = max(0, (float) $invoice->total - (float) $invoice->paid);

    $invoiceDate = $invoice->date ? Carbon::parse($invoice->date)->format('Y-m-d') : '-';
    $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date)->format('Y-m-d') : '-';

    $branchName = $invoice->branch?->name ?? '-';
    $representativeName = $invoice->representative?->name ?? '-';

    $ownerIsClient = !empty($invoice->client_id) && $invoice->client;
    $ownerIsRepresentative = !empty($invoice->representatives_id) && $invoice->representative;

    $companyName = $settings?->site_name ?? ($isArabic ? 'اسم الشركة' : 'Company Name');
    $companyEmail = $settings?->site_email ?? null;
    $companyPhone = $settings?->phone ?? null;
    $companyWebsite = $settings?->site_url ?? null;
    $companyAddress = $settings?->address ?? null;
@endphp

<body class="{{ $isArabic ? 'rtl' : 'ltr' }}">

    {{-- Header --}}
    <table class="header-table" style="margin-bottom: 18px;">
        <tr>
            <td style="width: 18%; {{ $isArabic ? 'text-align:right;' : 'text-align:left;' }}">
                @if (!empty($settings?->logo_path))
                    <img
                        src="{{ public_path('storage/' . $settings->logo_path) }}"
                        class="logo"
                        alt="Logo"
                    >
                @endif
            </td>

            <td style="width: 82%; {{ $isArabic ? 'text-align:left;' : 'text-align:right;' }}">
                <div class="company-name">{{ $companyName }}</div>

                @if($companyEmail)
                    <div class="company-meta">
                        <span class="label">{{ $isArabic ? 'الإيميل:' : 'Email:' }}</span>
                        {{ $companyEmail }}
                    </div>
                @endif

                @if($companyPhone)
                    <div class="company-meta">
                        <span class="label">{{ $isArabic ? 'الهاتف:' : 'Phone:' }}</span>
                        {{ $companyPhone }}
                    </div>
                @endif

                @if($companyWebsite)
                    <div class="company-meta">
                        <span class="label">{{ $isArabic ? 'الموقع:' : 'Website:' }}</span>
                        {{ $companyWebsite }}
                    </div>
                @endif

                @if($companyAddress)
                    <div class="company-meta">
                        <span class="label">{{ $isArabic ? 'العنوان:' : 'Address:' }}</span>
                        {{ $companyAddress }}
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Invoice main info --}}
    <div class="section">
        <table class="info-table">
            <tr>
                <td style="width: 50%;">
                    <div class="title mb-0">
                        {{ $isArabic ? 'فاتورة' : 'Invoice' }} #{{ $invoice->number }}
                    </div>

                    <div class="mt-10">
                        <span class="invoice-badge">
                            {{ $isArabic ? 'حالة الدفع' : 'Payment Status' }}:
                            {{ $invoice->payment_status ?? '-' }}
                        </span>
                    </div>
                </td>

                <td style="width: 50%; {{ $isArabic ? 'text-align:right;' : 'text-align:left;' }}">
                    <div class="mb-6">
                        <span class="label">{{ $isArabic ? 'تاريخ الإصدار:' : 'Issue Date:' }}</span>
                        {{ $invoiceDate }}
                    </div>

                    <div class="mb-6">
                        <span class="label">{{ $isArabic ? 'تاريخ الاستحقاق:' : 'Due Date:' }}</span>
                        {{ $dueDate }}
                    </div>

                    <div class="mb-6">
                        <span class="label">{{ $isArabic ? 'العملة:' : 'Currency:' }}</span>
                        {{ $currencyLabel }}
                    </div>

                    <div class="mb-6">
                        <span class="label">{{ $isArabic ? 'الفرع:' : 'Branch:' }}</span>
                        {{ $branchName }}
                    </div>

                    @if($ownerIsRepresentative)
                        <div class="mb-6">
                            <span class="label">{{ $isArabic ? 'المندوب:' : 'Representative:' }}</span>
                            {{ $representativeName }}
                        </div>
                    @endif

                    <div>
                        <span class="label">{{ $isArabic ? 'المتبقي:' : 'Remaining:' }}</span>
                        {{ number_format((float) $remainingAmount, 2) }} {{ $currencyLabel }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Party info + summary --}}
    <table class="two-col-table" style="margin-bottom: 16px;">
        <tr>
            <td style="width: 49%; vertical-align: top;">
                <div class=" party-box">
                    @if($ownerIsClient)
                        <div class="section-title">
                            {{ $isArabic ? 'بيانات العميل' : 'Client Information' }}
                        </div>

                        <div class="info-line"><strong>{{ $invoice->client->name ?? '-' }}</strong></div>

                        @if(!empty($invoice->client->phone))
                            <div class="info-line">{{ $invoice->client->phone }}</div>
                        @endif

                        @if(!empty($invoice->client->email))
                            <div class="info-line">{{ $invoice->client->email }}</div>
                        @endif

                        @if(!empty($invoice->client->country))
                            <div class="info-line">{{ $invoice->client->country }}</div>
                        @endif

                        @if(!empty($invoice->client->address))
                            <div class="info-line">{{ $invoice->client->address }}</div>
                        @endif

                        @if(!empty($invoice->client->tax_number))
                            <div class="info-line">
                                <span class="label">{{ $isArabic ? 'الرقم الضريبي:' : 'Tax Number:' }}</span>
                                <span class="value-block">{{ $invoice->client->tax_number }}</span>
                            </div>
                        @endif
                    @elseif($ownerIsRepresentative)
                        <div class="section-title">
                            {{ $isArabic ? 'بيانات المندوب' : 'Representative Information' }}
                        </div>

                        <div class="info-line"><strong>{{ $invoice->representative->name ?? '-' }}</strong></div>

                        @if(!empty($invoice->representative->phone))
                            <div class="info-line">{{ $invoice->representative->phone }}</div>
                        @endif

                        @if(!empty($invoice->representative->email))
                            <div class="info-line">{{ $invoice->representative->email }}</div>
                        @endif

                        @if(!empty($invoice->representative->address))
                            <div class="info-line">{{ $invoice->representative->address }}</div>
                        @endif
                    @else
                        <div class="section-title">
                            {{ $isArabic ? 'البيانات' : 'Information' }}
                        </div>
                        <div class="info-line">-</div>
                    @endif
                </div>
            </td>

            <td style="width: 2%;"></td>

            <td style="width: 49%; vertical-align: top;">
                <div class="     summary-box">
                    <div class="section-title">
                        {{ $isArabic ? 'ملخص الفاتورة' : 'Invoice Summary' }}
                    </div>

                    <div class="info-line">
                        <span class="label">{{ $isArabic ? 'رقم الفاتورة:' : 'Invoice No:' }}</span>
                        <span class="value-block">{{ $invoice->number }}</span>
                    </div>

                    <div class="info-line">
                        <span class="label">{{ $isArabic ? 'طريقة الدفع:' : 'Payment Method:' }}</span>
                        <span class="value-block">{{ $invoice->payment_method ?? '-' }}</span>
                    </div>

                    <div class="info-line">
                        <span class="label">{{ $isArabic ? 'المدفوع:' : 'Paid:' }}</span>
                        <span class="value-block">{{ number_format((float) $invoice->paid, 2) }} {{ $currencyLabel }}</span>
                    </div>

                    <div class="info-line">
                        <span class="label">{{ $isArabic ? 'الإجمالي:' : 'Total:' }}</span>
                        <span class="value-block">{{ number_format((float) $invoice->total, 2) }} {{ $currencyLabel }}</span>
                    </div>

                    <div class="info-line">
                        <span class="label">{{ $isArabic ? 'المتبقي:' : 'Remaining:' }}</span>
                        <span class="value-block">{{ number_format((float) $remainingAmount, 2) }} {{ $currencyLabel }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Items --}}
    <div class="section">
        <div class="section-title">
            {{ $isArabic ? 'بنود الفاتورة' : 'Invoice Items' }}
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 24%;">{{ $isArabic ? 'الصنف' : 'Item' }}</th>
                    <th style="width: 9%;">{{ $isArabic ? 'النوع' : 'Type' }}</th>
                    <th style="width: 12%;">{{ $isArabic ? 'السعر' : 'Unit Price' }}</th>
                    <th style="width: 8%;">{{ $isArabic ? 'الكمية' : 'Qty' }}</th>
                    <th style="width: 12%;">{{ $isArabic ? 'الخصم' : 'Discount' }}</th>
                    <th style="width: 12%;">{{ $isArabic ? 'الضريبة' : 'Tax' }}</th>
                    <th style="width: 18%;">{{ $isArabic ? 'الإجمالي' : 'Total' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($invoice->items as $idx => $it)
                    @php
                        $itemDiscountText = '-';

                        if (($it->discount_type ?? 'none') === 'percent') {
                            $itemDiscountText = number_format((float) ($it->discount_value ?? 0), 2) . '%';
                        } elseif (($it->discount_type ?? 'none') === 'amount') {
                            $itemDiscountText = number_format((float) ($it->line_discount ?? $it->discount_value ?? 0), 2) . ' ' . $currencyLabel;
                        }

                        $itemTaxText = '-';
                        if ((float)($it->line_tax ?? 0) > 0) {
                            $itemTaxText = number_format((float)($it->line_tax ?? 0), 2) . ' ' . $currencyLabel;
                        }
                    @endphp

                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>

                        <td>
                            <strong>{{ $it->product_name ?: ($isArabic ? 'خدمة' : 'Service') }}</strong>

                            @if(!empty($it->product_description))
                                <div class="muted small">{{ $it->product_description }}</div>
                            @endif
                        </td>

                        <td class="text-center">
                            {{ $it->item_type === 'service'
                                ? ($isArabic ? 'خدمة' : 'Service')
                                : ($isArabic ? 'منتج' : 'Product') }}
                        </td>

                        <td class="text-center">
                            {{ number_format((float) $it->unit_price, 2) }} {{ $currencyLabel }}
                        </td>

                        <td class="text-center">
                            {{ number_format((float) $it->quantity, 2) }}
                        </td>

                        <td class="text-center">
                            {{ $itemDiscountText }}
                        </td>

                        <td class="text-center">
                            {{ $itemTaxText }}
                        </td>

                        <td class="text-center">
                            {{ number_format((float) $it->line_total, 2) }} {{ $currencyLabel }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            {{ $isArabic ? 'لا توجد بنود في الفاتورة' : 'No items found' }}
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
                    <td class="label-cell">{{ $isArabic ? 'الخصم العام' : 'Invoice Discount' }}</td>
                    <td class="{{ $isArabic ? 'text-left' : 'text-right' }}">
                        {{ number_format((float) $invoice->discount, 2) }} {{ $currencyLabel }}
                    </td>
                </tr>

                <tr>
                    <td class="label-cell">{{ $isArabic ? 'إجمالي الضريبة' : 'Tax Total' }}</td>
                    <td class="{{ $isArabic ? 'text-left' : 'text-right' }}">
                        {{ number_format((float) $invoice->tax_total, 2) }} {{ $currencyLabel }}
                    </td>
                </tr>

                <tr class="grand-total-row">
                    <td class="label-cell">{{ $isArabic ? 'الإجمالي النهائي' : 'Grand Total' }}</td>
                    <td class="{{ $isArabic ? 'text-left' : 'text-right' }}">
                        {{ number_format((float) $invoice->total, 2) }} {{ $currencyLabel }}
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
                        <strong>{{ number_format((float) $remainingAmount, 2) }} {{ $currencyLabel }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>

        @if ($invoice->notes)
            <div class="notes-box">
                <strong>{{ $isArabic ? 'ملاحظات:' : 'Notes:' }}</strong><br>
                {{ $invoice->notes }}
            </div>
        @endif

        @if (!empty($settings?->invoice_footer))
            <div class="footer-box">
                {!! $settings->invoice_footer !!}
            </div>
        @endif
    </div>

</body>
</html>