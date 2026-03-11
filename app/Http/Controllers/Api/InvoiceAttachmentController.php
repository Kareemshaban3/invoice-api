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
        $attachments = $invoice->attachments()
            ->latest('id')
            ->get()
            ->map(function ($attachment) {
                $attachment->url = Storage::disk('public')->url($attachment->file_path);
                return $attachment;
            });

        return response()->json([
            'data' => $attachments,
        ]);
    }

    public function store(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:5120'],
            'type' => ['nullable', 'in:contract,design,purchase_order,other'],
        ]);

        $file = $request->file('file');
        $path = $file->store('invoice_attachments', 'public');

        $attachment = $invoice->attachments()->create([
            'type' => $data['type'] ?? 'other',
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);

        $attachment->url = Storage::disk('public')->url($attachment->file_path);

        return response()->json([
            'message' => 'Attachment uploaded successfully',
            'data' => $attachment,
        ], 201);
    }

    public function show(Invoice $invoice, InvoiceAttachment $attachment)
    {
        $attachment = $invoice->attachments()
            ->whereKey($attachment->id)
            ->firstOrFail();

        $attachment->url = Storage::disk('public')->url($attachment->file_path);

        return response()->json([
            'data' => $attachment,
        ]);
    }

    public function update(Request $request, Invoice $invoice, InvoiceAttachment $attachment)
    {
        $attachment = $invoice->attachments()
            ->whereKey($attachment->id)
            ->firstOrFail();

        $data = $request->validate([
            'file' => ['nullable', 'file', 'max:5120'],
            'type' => ['nullable', 'in:contract,design,purchase_order,other'],
        ]);

        if ($request->hasFile('file')) {
            if ($attachment->file_path) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $file = $request->file('file');
            $newPath = $file->store('invoice_attachments', 'public');

            $attachment->file_path = $newPath;
            $attachment->original_name = $file->getClientOriginalName();
        }

        if (array_key_exists('type', $data)) {
            $attachment->type = $data['type'] ?? 'other';
        }

        $attachment->save();

        $attachment->url = Storage::disk('public')->url($attachment->file_path);

        return response()->json([
            'message' => 'Attachment updated successfully',
            'data' => $attachment,
        ]);
    }

    public function destroy(Invoice $invoice, InvoiceAttachment $attachment)
    {
        $attachment = $invoice->attachments()
            ->whereKey($attachment->id)
            ->firstOrFail();

        if ($attachment->file_path) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'message' => 'Attachment deleted successfully',
        ]);
    }
}
