<?php
namespace Msdev2\Shopify\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Msdev2\Shopify\Http\Controllers\BaseController;
use Illuminate\Support\Str;

class EnvController extends BaseController
{
    public function index(Request $request)
    {
        // Detect subdomain from request
        $host = $request->getHost(); // e.g., "whatsapp.styfly.in" or "rain.styfly.in"
        $subdomain = null;
        
        // Extract subdomain (first part before the main domain)
        $parts = explode('.', $host);
        if (count($parts) >= 3) {
            // Has subdomain (e.g., whatsapp.styfly.in)
            $subdomain = $parts[0];
        }
        
        // Look for modules directory - check both in base_path and parent directory
        $modulesPath = base_path('modules');
        if (!File::exists($modulesPath)) {
            // Try parent directory (for Laravel apps where modules are at root level)
            $modulesPath = dirname(base_path()) . '/modules';
        }
        
        $modules = [];
        if (File::exists($modulesPath) && File::isDirectory($modulesPath)) {
            $modules = array_map('basename', File::directories($modulesPath));
        }

        // Default to root .env
        $envPath = base_path('.env');
        $currentFile = '.env (Root)';

        // Priority 1: If module is explicitly requested via query param
        if ($request->has('module')) {
            if ($request->module === 'root') {
                // Explicitly requested root .env
                $envPath = base_path('.env');
                $currentFile = '.env (Root)';
            } else {
                $moduleName = $request->module;
                
                // Try base_path first
                $moduleEnvPath = base_path("modules/{$moduleName}/.env");
                if (!File::exists($moduleEnvPath)) {
                    // Try parent directory
                    $moduleEnvPath = dirname(base_path()) . "/modules/{$moduleName}/.env";
                }
                
                if (File::exists($moduleEnvPath)) {
                    $envPath = $moduleEnvPath;
                    $currentFile = "modules/{$moduleName}/.env";
                }
            }
        }
        // Priority 2: Auto-detect based on subdomain
        elseif ($subdomain && in_array($subdomain, $modules)) {
            // Try base_path first
            $moduleEnvPath = base_path("modules/{$subdomain}/.env");
            if (!File::exists($moduleEnvPath)) {
                // Try parent directory
                $moduleEnvPath = dirname(base_path()) . "/modules/{$subdomain}/.env";
            }
            
            if (File::exists($moduleEnvPath)) {
                $envPath = $moduleEnvPath;
                $currentFile = "modules/{$subdomain}/.env";
            }
        }

        $content = '';
        if (File::exists($envPath)) {
            $content = File::get($envPath);
        }

        return view('msdev2::admin.env', compact('content', 'currentFile', 'modules', 'envPath'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'env_content' => 'required|string',
            'env_path' => 'required|string'
        ]);
        
        $envPath = $request->env_path;

        // Security check: ensure path is within base_path
        if (!Str::startsWith($envPath, base_path())) {
             return back()->with('error', 'Invalid file path.');
        }

        try {
            File::put($envPath, $request->env_content);
            
            // Clear config cache so changes take effect
            Artisan::call('config:clear');
            
            return back()->with('success', 'Environment file updated successfully and config cache cleared.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save file: ' . $e->getMessage());
        }
    }
}