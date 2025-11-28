<?php
namespace Msdev2\Shopify\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Msdev2\Shopify\Http\Controllers\BaseController;

class EnvController extends BaseController
{
    public function index()
    {
        $envPath = base_path('.env');
        $content = '';

        if (File::exists($envPath)) {
            $content = File::get($envPath);
        }

        return view('msdev2::admin.env', compact('content'));
    }

    public function update(Request $request)
    {
        $request->validate(['env_content' => 'required|string']);
        $envPath = base_path('.env');

        try {
            File::put($envPath, $request->env_content);
            
            // Clear config cache so changes take effect
            Artisan::call('config:clear');
            
            return back()->with('success', '.env updated successfully and config cache cleared.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save .env file: ' . $e->getMessage());
        }
    }
}