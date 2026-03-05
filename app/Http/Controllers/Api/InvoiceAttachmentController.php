<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceAttachmentController extends Controller
{

    public function index(Invoice $invoice)
    {
        return response()->json([
            'data' => $invoice->attachments()->latest('id')->get()
        ]);
    }
    public function store(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:5120'],
            'type' => ['nullable', 'in:contract,design,purchase_order,other'],
        ]);

        $path = $request->file('file')->store('invoice_attachments', 'public');

        $attachment = $invoice->attachments()->create([
            'type' => $data['type'] ?? 'other',
            'file_path' => $path,
            'original_name' => $request->file('file')->getClientOriginalName(),
        ]);

        return response()->json([
            'message' => 'Attachment uploaded',
            'data' => $attachment
        ]);
    }


    public function destroy(InvoiceAttachment $attachment)
    {
        Storage::disk('public')->delete($attachment->file_path);

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted'
        ]);
    }
}
