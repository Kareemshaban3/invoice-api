<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    public function show()
    {
        return SiteSetting::first() ?? SiteSetting::create([]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['nullable','string','max:255'],
            'site_url' => ['nullable','string','max:255'],
            'site_email' => ['nullable','email','max:255'],
            'items_per_page' => ['required','integer','min:1','max:200'],
            'default_currency' => ['required','in:EGP,SAR,USD,OMR,QAR'],
            'invoice_footer' => ['nullable','string'],
        ]);

        $settings = SiteSetting::first() ?? SiteSetting::create([]);
        $settings->update($data);

        return response()->json(['message' => 'Saved', 'data' => $settings]);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required','image','mimes:png,jpg,jpeg,webp','max:2048'],
        ]);

        $settings = SiteSetting::first() ?? SiteSetting::create([]);

        if ($settings->logo_path) {
            Storage::disk('public')->delete($settings->logo_path);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $settings->logo_path = $path;
        $settings->save();

        return response()->json(['message' => 'Logo uploaded', 'data' => $settings]);
    }
}
