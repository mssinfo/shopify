<?php
namespace Msdev2\Shopify\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Msdev2\Shopify\Http\Controllers\BaseController;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Models\Charge;
use Msdev2\Shopify\Mail\MarketingMail;

class MarketingController extends BaseController
{
    public function index()
    {
        // Get Plans for Filter
        $plans = Charge::where('status', 'active')->distinct()->pluck('name');
        $templates = config('marketingTemplates');
        return view('msdev2::admin.marketing', compact('plans', 'templates'));
    }
    /**
     * Renders the email view to HTML string for the preview modal
     */
    public function preview(Request $request)
    {
        $request->validate([
            'subject' => 'required',
            'message' => 'required'
        ]);

        // Render the specific email blade with data
        // Note: Using render() creates the HTML string
        $html = view('msdev2::emails.marketing', [
            'shopInfo' => Shop::first(), // Sample shop info for preview
            'subjectLine' => str_replace(['[App Name]'],[config('app.name')],$request->subject),
            'content' => str_replace(['[App Name]'],[config('app.name')],$request->message),
            'bannerImage' => null // Or add capability to upload
        ])->render();

        return response()->json(['html' => $html]);
    }
    public function export(Request $request)
    {
        $request->validate([
            'target' => 'required', // all, plan, specific
        ]);

        $query = Shop::query();

        // 1. Filtering Logic (Same as send)
        if ($request->target === 'all_active') {
            $query->where('is_uninstalled', 0);
        } 
        elseif ($request->target === 'uninstalled') {
            $query->where('is_uninstalled', 1);
        }
        elseif ($request->target === 'plan') {
            $planName = $request->plan_name;
            if ($planName === 'freemium') {
                $query->where('is_uninstalled', 0)->doesntHave('activeCharge');
            } else {
                $query->whereHas('charges', function($q) use ($planName) {
                    $q->where('status', 'active')->where('name', $planName);
                });
            }
        }
        elseif ($request->target === 'specific') {
            $targets = array_map('trim', explode(',', $request->specific_shops));
            $query->whereIn('shop', $targets)->orWhereIn('domain', $targets);
        }

        $shops = $query->get();

        if ($shops->isEmpty()) {
            return back()->with('error', 'No shops found matching your criteria.');
        }

        // Generate CSV
        $fileName = 'email_export_' . date('Y-m-d_H-i-s') . '.csv';
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function() use($shops) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array('Shop Domain', 'Shop Name', 'Owner Name', 'Email', 'Customer Email', 'Plan', 'Status'));

            foreach ($shops as $shop) {
                $detail = !empty($shop->detail) ? (is_string($shop->detail) ? json_decode($shop->detail, true) : $shop->detail) : [];
                $email = $detail['email'] ?? '';
                $customerEmail = $detail['customer_email'] ?? '';
                $plan = $shop->activeCharge ? $shop->activeCharge->name : 'Freemium';
                $status = $shop->is_uninstalled ? 'Uninstalled' : 'Active';

                fputcsv($file, array(
                    $shop->shop,
                    $detail['name'] ?? '',
                    $detail['shop_owner'] ?? '',
                    $email,
                    $customerEmail,
                    $plan,
                    $status
                ));
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function send(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'target' => 'required', // all, plan, specific
        ]);

        $query = Shop::query();

        // 1. Filtering Logic
        if ($request->target === 'all_active') {
            $query->where('is_uninstalled', 0);
        } 
        elseif ($request->target === 'uninstalled') {
            $query->where('is_uninstalled', 1);
        }
        elseif ($request->target === 'plan') {
            $planName = $request->plan_name;
            if ($planName === 'freemium') {
                $query->where('is_uninstalled', 0)->doesntHave('activeCharge');
            } else {
                $query->whereHas('charges', function($q) use ($planName) {
                    $q->where('status', 'active')->where('name', $planName);
                });
            }
        }
        elseif ($request->target === 'specific') {
            // Expecting comma separated domains or IDs
            $targets = array_map('trim', explode(',', $request->specific_shops));
            $query->whereIn('shop', $targets)->orWhereIn('domain', $targets);
        }

        $shops = $query->get();

        if ($shops->isEmpty()) {
            return back()->with('error', 'No shops found matching your criteria.');
        }

        // 2. Sending Logic (Using Queue is recommended for production)
        $count = 0;
        foreach ($shops as $shopInfo) {
            // Try to find the best email
            $email = null;
            
            // 1. Check Metadata/Detail JSON
            if (!empty($shopInfo->detail)) {
                $detail = is_string($shopInfo->detail) ? json_decode($shopInfo->detail, true) : $shopInfo->detail;
                $email = $detail['email'] ?? ($detail['customer_email'] ?? null);
            }

            // 2. Fallback to User relation if you use it
            if (!$email && $shopInfo->user_id) {
                // $email = $shop->user->email; 
            }

            if ($email) {
                try {
                    $subjectLine = str_replace(
                        ['[Shop Name]', '[App Name]'],
                        [$shopInfo->name, config('app.name')],
                        $request->subject
                    );
                    $messageContent = str_replace(
                        ['[Shop Name]', '[App Name]'],
                        [$shopInfo->name, config('app.name')],
                        $request->message
                    );
                    Mail::to($email)->send(new MarketingMail($subjectLine, $messageContent, $shopInfo));
                    $count++;
                } catch (\Exception $e) {
                    // Log error, continue to next
                }
            }
        }

        return back()->with('success', "Campaign started! Email sent to {$count} shops.");
    }
}