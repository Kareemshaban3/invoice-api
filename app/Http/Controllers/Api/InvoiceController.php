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
use Illuminate\Support\Facades\Mail;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = Invoice::query();
        $perPage = (int) $request->attributes->get('per_page', 10);


        if ($s = $request->query('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('number', 'like', "%$s%")
                    ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%$s%"));
            });
        }
        if ($currenc = $request->query('currency')) {
            $q->where('currency', 'like', "%$currenc%");
        }

        if ($clientId = $request->query('client_id')) {
            $q->where('client_id', $clientId);
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

        return $q->latest('id')->paginate($perPage);
    }

    public function store(StoreInvoiceRequest $request)
    {
        $client = Client::findOrFail($request->client_id);

        $invoice = DB::transaction(function () use ($request, $client) {
            $currency = $request->currency;
            $discount = (float) ($request->input('discount', 0));
            $paid = (float) ($request->input('paid', 0));

            $itemsReq = $request->items;
            ['items' => $items, 'subtotal' => $subtotal] = $this->buildInvoiceItems($itemsReq, $currency);

            $total = max(0, $subtotal - $discount);

            $inv = Invoice::create([
                'client_id' => $client->id,
                'number' => 'TMP',
                'date' => $request->date,
                'due_date' => $request->due_date,
                'currency' => $currency,
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'total' => round($total, 2),
                'paid' => min($paid, $total),
                'notes' => $request->notes,
            ]);

            $inv->number = $this->numberFromId($inv->id);
            $inv->save();

            foreach ($items as $it) {
                $inv->items()->create($it);
            }

            return $inv->load(['client', 'items']);
        });

        return response()->json(['message' => __('messages.created'), 'data' => $invoice], 201);
    }

    public function show(Request $request, Invoice $invoice)
    {
        return $invoice->load(['client', 'items']);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $updated = DB::transaction(function () use ($request, $invoice) {
            if ($request->has('client_id')) {
                $client = Client::findOrFail($request->client_id);
                $invoice->client_id = $client->id;
            }

            $invoice->fill($request->only(['date', 'due_date', 'currency', 'discount', 'notes']));

            if ($request->has('items')) {
                $currency = $request->input('currency', $invoice->currency);

                $itemsReq = $request->items;
                ['items' => $items, 'subtotal' => $subtotal] = $this->buildInvoiceItems($itemsReq, $currency);

                $discount = (float) $request->input('discount', $invoice->discount);
                $total = max(0, $subtotal - $discount);

                $invoice->currency = $currency;
                $invoice->subtotal = round($subtotal, 2);
                $invoice->discount = round($discount, 2);
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
            return $invoice->load(['client', 'items']);
        });

        return response()->json(['message' => __('messages.updated'), 'data' => $updated]);
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $invoice->delete();
        return response()->json(['message' => __('messages.deleted')]);
    }
    private function buildInvoiceItems(array $itemsReq, string $currency): array
    {
        $productIds = collect($itemsReq)->pluck('product_id')->unique()->values();

        $products = Product::whereIn('id', $productIds)
            ->with(['prices' => fn($price) => $price->where('currency', $currency)])
            ->lockForUpdate() 
            ->get()
            ->keyBy('id');

        $missingProductIds = $productIds->diff($products->keys());
        if ($missingProductIds->isNotEmpty()) {
            abort(422, __('messages.invalid_products'));
        }

        $items = [];
        $subtotal = 0.0;

        foreach ($itemsReq as $row) {
            $product = $products[$row['product_id']];
            $productPrice = $product->prices->first();

            if (!$productPrice) {
                abort(422, __('messages.currency_mismatch'));
            }

            $qty = (int) $row['quantity'];
            if ($qty <= 0) {
                abort(422, __('messages.invalid_quantity'));
            }

            // ✅ تحقق المخزون
            if ((int)$product->stock < $qty) {
                abort(422, __('messages.out_of_stock'));
            }

            // ✅ خصم المخزون
            $product->decrement('stock', $qty);

            $unit = (float) $productPrice->price;
            $line = $qty * $unit;

            $subtotal += $line;

            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_description' => $product->description,
                'unit_price' => $unit,
                'quantity' => $qty,
                'line_total' => $line,
            ];
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
        ];
    }


    private function numberFromId(int $id): string
    {
        return 'INV-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
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
