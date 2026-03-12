<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendInvoiceEmailRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Mail\InvoiceMail;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Throwable;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = Invoice::query()->with([
            'client',
            'currency',
            'branch',
            'representative',
        ]);

        $perPage = (int) $request->attributes->get('per_page', 10);

        if ($s = $request->query('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%{$s}%")
                    ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('representative', fn($r) => $r->where('name', 'like', "%{$s}%"));
            });
        }

        if ($currencyId = $request->query('currency_id')) {
            $q->where('currency_id', $currencyId);
        }

        if ($clientId = $request->query('client_id')) {
            $q->where('client_id', $clientId);
        }

        if ($representativeId = $request->query('representatives_id')) {
            $q->where('representatives_id', $representativeId);
        }

        if ($branchId = $request->query('branches_id')) {
            $q->where('branches_id', $branchId);
        }

        if ($paymentStatus = $request->query('payment_status')) {
            $q->where('payment_status', $paymentStatus);
        }

        if ($status = $request->query('status')) {
            if ($status === 'unpaid') {
                $q->where('paid', '<=', 0);
            } elseif ($status === 'paid') {
                $q->whereColumn('paid', '>=', 'total');
            } elseif ($status === 'partial') {
                $q->where('paid', '>', 0)->whereColumn('paid', '<', 'total');
            }
        }

        if ($request->boolean('overdue')) {
            $q->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereIn('payment_status', ['unpaid', 'partial']);
        }

        return $q->latest('id')->paginate($perPage);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $client = null;

        if ($request->filled('client_id')) {
            $client = Client::findOrFail($request->client_id);
        }

        $invoice = DB::transaction(function () use ($request, $client) {
            $currencyId = (int) $request->currency_id;
            $currency = Currency::findOrFail($currencyId);

            $invoiceDiscount = (float) ($request->input('discount', 0));
            $paid = (float) ($request->input('paid', 0));

            $paymentMethod = $request->input('payment_method', 'cash');
            $paymentStatus = $request->input('payment_status', 'draft');

            [
                'items' => $items,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
            ] = $this->buildInvoiceItems($request->items, $currency->id);

            $total = max(0, ($subtotal + $taxTotal) - $invoiceDiscount);
            $paid = min($paid, $total);

            if ($client) {
                $currentDue = Invoice::where('client_id', $client->id)
                    ->whereColumn('total', '>', 'paid')
                    ->sum(DB::raw('total - paid'));

                if ($client->credit_limit > 0 && ($currentDue + $total) > $client->credit_limit) {
                    abort(422, 'Credit limit exceeded');
                }
            }

            $inv = Invoice::create([
                'client_id' => $request->filled('client_id') ? $client?->id : null,
                'representatives_id' => $request->filled('representatives_id') ? $request->input('representatives_id') : null,
                'branches_id' => $request->input('branches_id'),
                'number' => 'TMP',
                'date' => $request->date,
                'due_date' => $request->due_date,
                'currency_id' => $currency->id,
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'subtotal' => round($subtotal, 2),
                'discount' => round($invoiceDiscount, 2),
                'tax_total' => round($taxTotal, 2),
                'total' => round($total, 2),
                'paid' => round($paid, 2),
                'notes' => $request->notes,
            ]);

            $inv->number = $this->numberFromId($inv->id);
            $inv->save();

            foreach ($items as $it) {
                $inv->items()->create($it);
            }

            $this->syncPaymentStatus($inv);

            return $inv->load([
                'client',
                'currency',
                'items',
                'attachments',
                'branch',
                'representative',
            ]);
        });

        return response()->json([
            'message' => __('messages.created'),
            'data' => $invoice,
            'summary' => [
                'subtotal' => $invoice->subtotal,
                'discount' => $invoice->discount,
                'tax_total' => $invoice->tax_total,
                'total' => $invoice->total,
                'paid' => $invoice->paid,
                'remaining_amount' => $invoice->remaining_amount,
            ],
        ], 201);
    }

    public function show(Request $request, Invoice $invoice)
    {
        return $invoice->load([
            'client',
            'currency',
            'items',
            'attachments',
            'branch',
            'representative',
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $updated = DB::transaction(function () use ($request, $invoice) {
            if ($request->has('items')) {
                $this->restoreStockFromInvoice($invoice);
            }

            if ($request->has('client_id')) {
                $invoice->client_id = $request->filled('client_id')
                    ? Client::findOrFail($request->client_id)->id
                    : null;
            }

            if ($request->has('representatives_id')) {
                $invoice->representatives_id = $request->filled('representatives_id')
                    ? $request->input('representatives_id')
                    : null;
            }

            // ضمان أن واحد فقط هو الموجود
            if ($request->filled('client_id')) {
                $invoice->representatives_id = null;
            }

            if ($request->filled('representatives_id')) {
                $invoice->client_id = null;
            }

            if ($request->has('branches_id')) {
                $invoice->branches_id = $request->input('branches_id');
            }

            if ($request->has('currency_id')) {
                $currency = Currency::findOrFail((int) $request->input('currency_id'));
                $invoice->currency_id = $currency->id;
            }

            if ($request->has('payment_method')) {
                $invoice->payment_method = $request->input('payment_method', $invoice->payment_method);
            }

            if ($request->has('payment_status')) {
                $invoice->payment_status = $request->input('payment_status', $invoice->payment_status);
            }

            $invoice->fill($request->only([
                'date',
                'due_date',
                'discount',
                'notes',
            ]));

            if ($request->has('items')) {
                $currencyId = (int) $request->input('currency_id', $invoice->currency_id);

                [
                    'items' => $items,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                ] = $this->buildInvoiceItems($request->items, $currencyId);

                $invoiceDiscount = (float) $request->input('discount', $invoice->discount);
                $total = max(0, ($subtotal + $taxTotal) - $invoiceDiscount);

                $invoice->currency_id = $currencyId;
                $invoice->subtotal = round($subtotal, 2);
                $invoice->discount = round($invoiceDiscount, 2);
                $invoice->tax_total = round($taxTotal, 2);
                $invoice->total = round($total, 2);

                $invoice->items()->delete();

                foreach ($items as $it) {
                    $invoice->items()->create($it);
                }
            }

            if ($request->has('paid')) {
                $invoice->paid = min((float) $request->paid, (float) $invoice->total);
            }

            $invoice->save();

            $this->syncPaymentStatus($invoice);

            return $invoice->load([
                'client',
                'currency',
                'items',
                'attachments',
                'branch',
                'representative',
            ]);
        });

        return response()->json([
            'message' => __('messages.updated'),
            'data' => $updated,
            'summary' => [
                'subtotal' => $updated->subtotal,
                'discount' => $updated->discount,
                'tax_total' => $updated->tax_total,
                'total' => $updated->total,
                'paid' => $updated->paid,
                'remaining_amount' => $updated->remaining_amount,
            ],
        ]);
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        DB::transaction(function () use ($invoice) {
            $this->restoreStockFromInvoice($invoice);
            $invoice->delete();
        });

        return response()->json([
            'message' => __('messages.deleted'),
        ]);
    }

    private function buildInvoiceItems(array $itemsReq, int $currencyId): array
    {
        $currency = Currency::findOrFail($currencyId);

        $productIds = collect($itemsReq)
            ->where('item_type', 'product')
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        $products = Product::whereIn('id', $productIds)
            ->with([
                'prices' => fn($price) => $price->where('currency_id', $currency->id),
            ])
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $missing = $productIds->diff($products->keys());
        if ($missing->isNotEmpty()) {
            abort(422, __('messages.invalid_products'));
        }

        $items = [];
        $subtotal = 0.0;
        $discountTotal = 0.0;
        $taxTotal = 0.0;

        foreach ($itemsReq as $row) {
            $type = $row['item_type'] ?? 'product';
            $qty = (float) ($row['quantity'] ?? 1);

            if ($qty <= 0) {
                abort(422, __('messages.invalid_quantity'));
            }

            $unitPrice = 0.0;
            $name = '';
            $desc = null;
            $productId = null;

            if ($type === 'product') {
                $productId = (int) $row['product_id'];
                $product = $products[$productId] ?? null;

                if (!$product) {
                    abort(422, __('messages.invalid_products'));
                }

                $productPrice = $product->prices->first();
                if (!$productPrice) {
                    abort(422, __('messages.currency_mismatch'));
                }

                if ((int) $product->stock < (int) $qty) {
                    abort(422, __('messages.out_of_stock'));
                }

                $product->decrement('stock', (int) $qty);

                $unitPrice = (float) $productPrice->price;
                $name = $product->name;
                $desc = $product->description;

                $row['tax_type'] = $row['tax_type'] ?? $product->default_tax_type ?? 'no_tax';
                $row['tax_rate'] = $row['tax_rate'] ?? (float) ($product->default_tax_rate ?? 0);
            } else {
                $unitPrice = (float) ($row['unit_price'] ?? 0);
                $name = (string) ($row['name'] ?? 'Service');
                $desc = $row['description'] ?? null;
            }

            $lineSubtotal = $unitPrice * $qty;

            $discountType = $row['discount_type'] ?? 'none';
            $discountValue = (float) ($row['discount_value'] ?? 0);

            $lineDiscount = 0.0;
            if ($discountType === 'amount') {
                $lineDiscount = min($discountValue, $lineSubtotal);
            } elseif ($discountType === 'percent') {
                $lineDiscount = min(($discountValue / 100.0) * $lineSubtotal, $lineSubtotal);
            }

            $lineAfterDiscount = max(0, $lineSubtotal - $lineDiscount);

            $taxType = $row['tax_type'] ?? 'no_tax';
            $taxRate = (float) ($row['tax_rate'] ?? 0);

            $lineTax = 0.0;
            $lineTotal = $lineAfterDiscount;

            if ($taxType === 'exclusive' && $taxRate > 0) {
                $lineTax = ($taxRate / 100.0) * $lineAfterDiscount;
                $lineTotal = $lineAfterDiscount + $lineTax;
            } elseif ($taxType === 'inclusive' && $taxRate > 0) {
                $lineTax = $lineAfterDiscount - ($lineAfterDiscount / (1 + ($taxRate / 100.0)));
                $lineTotal = $lineAfterDiscount;
            }

            $subtotal += $lineAfterDiscount;
            $discountTotal += $lineDiscount;
            $taxTotal += $lineTax;

            $items[] = [
                'item_type' => $type,
                'product_id' => $productId,
                'product_name' => $name,
                'product_description' => $desc,
                'unit_price' => round($unitPrice, 2),
                'quantity' => round($qty, 2),
                'discount_type' => $discountType,
                'discount_value' => round($discountValue, 2),
                'tax_type' => $taxType,
                'tax_rate' => round($taxRate, 2),
                'line_subtotal' => round($lineSubtotal, 2),
                'line_discount' => round($lineDiscount, 2),
                'line_after_discount' => round($lineAfterDiscount, 2),
                'line_tax' => round($lineTax, 2),
                'line_total' => round($lineTotal, 2),
            ];
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
        ];
    }

    private function restoreStockFromInvoice(Invoice $invoice): void
    {
        $invoice->loadMissing('items');

        $productQtyMap = $invoice->items
            ->where('item_type', 'product')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(fn($rows) => (int) round($rows->sum('quantity')));

        if ($productQtyMap->isEmpty()) {
            return;
        }

        $products = Product::whereIn('id', $productQtyMap->keys())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($productQtyMap as $pid => $qty) {
            if (isset($products[$pid]) && $qty > 0) {
                $products[$pid]->increment('stock', $qty);
            }
        }
    }

    private function numberFromId(int $id): string
    {
        return 'INV-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    private function syncPaymentStatus(Invoice $invoice): void
    {
        if ($invoice->payment_status === 'cancelled') {
            return;
        }

        $total = (float) $invoice->total;
        $paid = (float) $invoice->paid;

        if ($total <= 0) {
            $invoice->payment_status = 'paid';
        } elseif ($paid <= 0) {
            $invoice->payment_status = 'unpaid';
        } elseif ($paid + 0.00001 < $total) {
            $invoice->payment_status = 'partial';
        } else {
            $invoice->payment_status = 'paid';
        }

        $invoice->save();
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        $invoice->load([
            'client',
            'currency',
            'items',
            'branch',
            'representative',
        ]);

        $fileName = $invoice->number . '.pdf';
        $pdfContent = $this->renderInvoicePdf($invoice);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$fileName}\"",
        ]);
    }

    public function pdfDownload(Invoice $invoice)
    {
        $invoice->load([
            'client',
            'currency',
            'items',
            'branch',
            'representative',
        ]);

        $fileName = $invoice->number . '.pdf';
        $pdfContent = $this->renderInvoicePdf($invoice);

        Storage::disk('local')->put("invoices/{$fileName}", $pdfContent);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    private function renderInvoicePdf(Invoice $invoice): string
    {
        $settings = SiteSetting::first();

        $mpdf = $this->buildMpdf();

        $html = view('pdf.invoice', [
            'invoice' => $invoice,
            'settings' => $settings,
        ])->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    private function buildMpdf(): Mpdf
    {
        $config = (new ConfigVariables())->getDefaults();
        $fontConfig = (new FontVariables())->getDefaults();

        $tempDir = storage_path('framework/cache/mpdf');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'fontDir' => array_merge($config['fontDir'], [storage_path('fonts')]),
            'fontdata' => $fontConfig['fontdata'] + [
                'cairo' => [
                    'R' => 'Cairo-Regular.ttf',
                    'B' => 'Cairo-Bold.ttf',
                ],
            ],
            'default_font' => 'cairo',
        ]);

        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetDirectionality(app()->getLocale() === 'ar' ? 'rtl' : 'ltr');

        return $mpdf;
    }

    public function sendEmail(SendInvoiceEmailRequest $request, Invoice $invoice)
    {
        $invoice->load([
            'client',
            'currency',
            'items',
            'branch',
            'representative',
        ]);

        $to = $request->filled('to')
            ? $request->input('to')
            : ($invoice->client->email ?? null);

        if (!$to) {
            return response()->json(['message' => 'Client email not found'], 422);
        }

        try {
            $pdfBinary = $this->renderInvoicePdf($invoice);
            $fileName = $invoice->number . '.pdf';

            $mailable = new InvoiceMail($invoice, $pdfBinary, $fileName);

            $cc = $request->input('cc', []);
            if (!empty($cc)) {
                $mailable->cc($cc);
            }

            \Mail::to($to)->send($mailable);

            return response()->json([
                'message' => app()->getLocale() === 'ar'
                    ? 'تم إرسال الفاتورة بالبريد'
                    : 'Invoice emailed successfully',
                'to' => $to,
            ]);
        } catch (Throwable $e) {
            Log::error('Invoice email failed', [
                'to' => $to,
                'invoice' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => app()->getLocale() === 'ar'
                    ? 'فشل إرسال البريد'
                    : 'Email sending failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}