<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendInvoiceEmailRequest;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Mail\InvoiceMail;
use App\Models\Client;
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
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = Invoice::query()->with('client'); // لتسريع البحث بالعميل
        $perPage = (int) $request->attributes->get('per_page', 10);

        if ($s = $request->query('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%$s%")
                    ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%$s%"));
            });
        }

        if ($currenc = $request->query('currency')) {
            $q->where('currency', strtoupper((string)$currenc));
        }

        if ($clientId = $request->query('client_id')) {
            $q->where('client_id', $clientId);
        }

        // payment_status الجديد (draft/unpaid/partial/paid/cancelled)
        if ($paymentStatus = $request->query('payment_status')) {
            $q->where('payment_status', $paymentStatus);
        }

        // backward compatible: status=unpaid/paid/partial (قديم)
        if ($status = $request->query('status')) {
            if ($status === 'unpaid') {
                $q->where('paid', '<=', 0);
            } elseif ($status === 'paid') {
                $q->whereColumn('paid', '>=', 'total');
            } elseif ($status === 'partial') {
                $q->where('paid', '>', 0)->whereColumn('paid', '<', 'total');
            }
        }

        // overdue filter
        if ($request->boolean('overdue')) {
            $q->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereIn('payment_status', ['unpaid', 'partial']);
        }

        return $q->latest('id')->paginate($perPage);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $client = Client::findOrFail($request->client_id);

        $invoice = DB::transaction(function () use ($request, $client) {
            $currency = strtoupper((string) $request->currency);
            $discount = (float) ($request->input('discount', 0));
            $paid = (float) ($request->input('paid', 0));

            $paymentMethod = $request->input('payment_method', 'cash');
            $paymentStatus = $request->input('payment_status', 'draft');

            $itemsReq = $request->items;

            [
                'items' => $items,
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
            ] = $this->buildInvoiceItems($itemsReq, $currency);

            // إجمالي الفاتورة = (Subtotal بعد خصم السطور + TaxTotal) - خصم عام
            $total = max(0, ($subtotal + $taxTotal) - $discount);
            $paid = min($paid, $total);

            // تحقق الحد الائتماني
            $currentDue = Invoice::where('client_id', $client->id)
                ->whereColumn('total', '>', 'paid')
                ->sum(DB::raw('total - paid'));

            if ($client->credit_limit > 0 && ($currentDue + $total) > $client->credit_limit) {
                abort(422, 'Credit limit exceeded');
            }

            $inv = Invoice::create([
                'client_id' => $client->id,
                'number' => 'TMP',
                'date' => $request->date,
                'due_date' => $request->due_date,
                'currency' => $currency,

                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,

                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
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

            return $inv->load(['client', 'items']);
        });

        return response()->json(['message' => __('messages.created'), 'data' => $invoice], 201);
    }

    public function show(Request $request, Invoice $invoice)
    {
        return $invoice->load(['client', 'items', 'attachments']);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $updated = DB::transaction(function () use ($request, $invoice) {
            // لو هتغير items لازم نرجع المخزون القديم الأول
            if ($request->has('items')) {
                $this->restoreStockFromInvoice($invoice);
            }

            if ($request->has('client_id')) {
                $client = Client::findOrFail($request->client_id);
                $invoice->client_id = $client->id;
            }

            // payment fields
            if ($request->has('payment_method')) {
                $invoice->payment_method = $request->input('payment_method', $invoice->payment_method);
            }
            if ($request->has('payment_status')) {
                $invoice->payment_status = $request->input('payment_status', $invoice->payment_status);
            }

            $invoice->fill($request->only(['date', 'due_date', 'currency', 'discount', 'notes']));

            if ($request->has('items')) {
                $currency = strtoupper((string) $request->input('currency', $invoice->currency));

                $itemsReq = $request->items;

                [
                    'items' => $items,
                    'subtotal' => $subtotal,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                ] = $this->buildInvoiceItems($itemsReq, $currency);

                $discount = (float) $request->input('discount', $invoice->discount);
                $total = max(0, ($subtotal + $taxTotal) - $discount);

                $invoice->currency = $currency;
                $invoice->subtotal = round($subtotal, 2);
                $invoice->discount = round($discount, 2);
                $invoice->tax_total = round($taxTotal, 2);
                $invoice->total = round($total, 2);

                // استبدال العناصر
                $invoice->items()->delete();
                foreach ($items as $it) {
                    $invoice->items()->create($it);
                }
            }

            if ($request->has('paid')) {
                $invoice->paid = min((float) $request->paid, (float) $invoice->total);
            }

            $invoice->save();

            // مزامنة payment_status حسب paid/total لو ماكانش cancelled
            $this->syncPaymentStatus($invoice);

            return $invoice->load(['client', 'items']);
        });

        return response()->json(['message' => __('messages.updated'), 'data' => $updated]);
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        DB::transaction(function () use ($invoice) {
            // رجّع المخزون قبل الحذف (للمنتجات فقط)
            $this->restoreStockFromInvoice($invoice);
            $invoice->delete();
        });

        return response()->json(['message' => __('messages.deleted')]);
    }

    
    private function buildInvoiceItems(array $itemsReq, string $currency): array
    {
        $productIds = collect($itemsReq)
            ->where('item_type', 'product')
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        $products = Product::whereIn('id', $productIds)
            ->with(['prices' => fn($price) => $price->where('currency', $currency)])
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

            if ($qty <= 0) abort(422, __('messages.invalid_quantity'));

            $unitPrice = 0.0;
            $name = '';
            $desc = null;
            $productId = null;

            if ($type === 'product') {
                $productId = (int) $row['product_id'];
                $product = $products[$productId] ?? null;

                if (!$product) abort(422, __('messages.invalid_products'));

                $productPrice = $product->prices->first();
                if (!$productPrice) abort(422, __('messages.currency_mismatch'));

                // ✅ تحقق المخزون
                if ((int)$product->stock < (int)$qty) abort(422, __('messages.out_of_stock'));

                // ✅ خصم المخزون
                $product->decrement('stock', (int)$qty);

                $unitPrice = (float) $productPrice->price;
                $name = $product->name;
                $desc = $product->description;

                // default tax from product if not provided
                $row['tax_type'] = $row['tax_type'] ?? $product->default_tax_type ?? 'no_tax';
                $row['tax_rate'] = $row['tax_rate'] ?? (float)($product->default_tax_rate ?? 0);
            } else {
                // service
                $unitPrice = (float) ($row['unit_price'] ?? 0);
                $name = (string) ($row['name'] ?? 'Service');
                $desc = $row['description'] ?? null;
            }

            $lineBase = $unitPrice * $qty;

            // discount per line
            $discountType = $row['discount_type'] ?? 'none';
            $discountValue = (float) ($row['discount_value'] ?? 0);

            $lineDiscount = 0.0;
            if ($discountType === 'amount') {
                $lineDiscount = min($discountValue, $lineBase);
            } elseif ($discountType === 'percent') {
                $lineDiscount = min(($discountValue / 100.0) * $lineBase, $lineBase);
            }

            $lineAfterDiscount = max(0, $lineBase - $lineDiscount);

            // tax per line
            $taxType = $row['tax_type'] ?? 'no_tax';
            $taxRate = (float) ($row['tax_rate'] ?? 0);

            $lineTax = 0.0;
            $lineTotal = $lineAfterDiscount;

            if ($taxType === 'exclusive' && $taxRate > 0) {
                $lineTax = ($taxRate / 100.0) * $lineAfterDiscount;
                $lineTotal = $lineAfterDiscount + $lineTax;
            } elseif ($taxType === 'inclusive' && $taxRate > 0) {
                $lineTax = $lineAfterDiscount - ($lineAfterDiscount / (1 + ($taxRate / 100.0)));
                $lineTotal = $lineAfterDiscount; // شامل
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

    /**
     * رجّع المخزون للمنتجات في الفاتورة قبل تعديل items أو حذف invoice
     */
    private function restoreStockFromInvoice(Invoice $invoice): void
    {
        $invoice->loadMissing('items');

        $productQtyMap = $invoice->items
            ->where('item_type', 'product')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(fn($rows) => (int) round($rows->sum('quantity')));

        if ($productQtyMap->isEmpty()) return;

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

    /**
     * مزامنة حالة الدفع بناءً على paid/total (لو مش cancelled)
     */
    private function syncPaymentStatus(Invoice $invoice): void
    {
        if ($invoice->payment_status === 'cancelled') return;

        $total = (float) $invoice->total;
        $paid  = (float) $invoice->paid;

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
        $invoice->load(['client', 'items']);

        $fileName = $invoice->number . '.pdf';
        $pdfContent = $this->renderInvoicePdf($invoice);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$fileName}\"",
        ]);
    }

    public function pdfDownload(Invoice $invoice)
    {
        $invoice->load(['client', 'items']);

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
        $invoice->load(['client', 'items']);

        $to = $request->filled('to') ? $request->input('to') : ($invoice->client->email ?? null);
        if (!$to) {
            return response()->json(['message' => 'Client email not found'], 422);
        }

        try {
            $pdfBinary = $this->renderInvoicePdf($invoice);
            $fileName  = $invoice->number . '.pdf';

            $mailable = new InvoiceMail($invoice, $pdfBinary, $fileName);

            $cc = $request->input('cc', []);
            if (!empty($cc)) {
                $mailable->cc($cc);
            }

            \Mail::to($to)->send($mailable);

            return response()->json([
                'message' => app()->getLocale() === 'ar' ? 'تم إرسال الفاتورة بالبريد' : 'Invoice emailed successfully',
                'to' => $to
            ]);
        } catch (Throwable $e) {
            Log::error('Invoice email failed', [
                'to' => $to,
                'invoice' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => app()->getLocale() === 'ar' ? 'فشل إرسال البريد' : 'Email sending failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
