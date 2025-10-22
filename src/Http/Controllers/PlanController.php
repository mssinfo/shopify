<?php
namespace Msdev2\Shopify\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Msdev2\Shopify\Lib\EnsureBilling;
use Msdev2\Shopify\Utils;
use Msdev2\Shopify\Events\PlanPageViewed;
use Msdev2\Shopify\Events\PlanProceeded;
use Msdev2\Shopify\Events\PlanPurchaseCompleted;

class PlanController extends BaseController{

    public function plan(Request $request){
        $shop = Utils::getShop();
        if (class_exists(PlanPageViewed::class)) {
            PlanPageViewed::dispatch($shop);
        }
        // $firstTimeCharge = $shop->charges()->first();
        $appUsed = $shop->appUsedDay();
        $activePlanName = $shop->activeCharge->name ?? '';
        $planList = config('msdev2.plan');
        $properties = [];
        $plans = [];
        $hasPlans = [];
        // dd($shop->detail);
        foreach ($planList as $plan) {
            $pList = [];
            $enable = true; 
            if(isset($plan["feature"]["plan"])){
                $enable = false; 
                $featurePlans = explode(",",($plan["feature"]["plan"].",partner_test,staff,all"));
                $myShopifyPlan = $shop->detail["plan_name"];
                if(in_array($myShopifyPlan,$featurePlans) || in_array(strtolower($shop->detail["plan_display_name"]),$featurePlans)){
                    $enable = true;
                }
            }
            $hasPlans[$plan['interval']] = true;
            foreach ($plan['properties'] as $property) {
                $pList[$property["name"]] = [
                    "value" => $property["value"] ?? "false",
                ];
                if(!in_array($property["name"],$properties)){
                    $properties[$property["name"]] = ["help_text"=>$property["help_text"] ?? '']; 
                }
            }
            $plan["properties"] = $pList;
            $plan["enable"] = $enable;
            $plans[] = $plan;
        }
        return view('msdev2::plan',compact('activePlanName','appUsed','properties','plans','hasPlans'));
    }
    public function planSubscribe(Request $request){
        $shop = Utils::getShop();
        $plan = $shop->plan($request->input("plan"));
        if (class_exists(PlanProceeded::class)) {
            PlanProceeded::dispatch($shop, $plan);
        }
        list($hasPayment, $confirmationUrl) = EnsureBilling::check($shop, $plan);
        if ($confirmationUrl) {
            return redirect($confirmationUrl);
        }
        return null;
    }
    public function planAccept(Request $request){
        $shop = Utils::getShop();
        $charges = $shop->activeCharge;
        if($charges){
            EnsureBilling::requestCancelSubscription($shop, 'gid://shopify/AppSubscription/'.$charges->charge_id);
            $charges->status = 'canceled';
            $charges->description = 'Cancel due to change plan';
            $charges->cancelled_on = Carbon::now();
            $charges->save();
        }
        $plan = $shop->plan($request->plan);
        $planType = 'recurring';
        $billingOn = Carbon::now()->addYear();
        if($plan["amount"] == 0){
            $planType = 'free';
            $billingOn = Carbon::now();
        }elseif($plan['interval'] == 'ONE_TIME'){
            $planType = 'one_time';
            $billingOn = Carbon::now();
        }elseif($plan['interval'] == 'EVERY_30_DAYS'){
            $billingOn = Carbon::now()->addMonth();
        }
        $trialDay = $plan['trialDays'] > $shop->appUsedDay() ? $plan['trialDays'] - $shop->appUsedDay() : 0;
        $shop->charges()->create([
            'charge_id'=>$request->charge_id ?? 0,
            'name'=>$plan["chargeName"],
            'test'=>!(app()->environment() === 'production'),
            'status'=>'active',
            'type'=>$planType,
            'price'=>$plan['amount'],
            'interval'=>$plan['interval'],
            'capped_amount'=>$plan['cappedAmount'] ?? 0,
            'trial_days'=>$trialDay,
            'billing_on'=>$billingOn,
            'activated_on'=>Carbon::now(),
            'trial_ends_on'=>Carbon::now()->addDays($trialDay),
        ]);

        if (class_exists(PlanPurchaseCompleted::class)) {
            PlanPurchaseCompleted::dispatch($shop, $request->charge_id ?? null, $plan["chargeName"]);
        }
        return redirect(Utils::Route('/'));
    }
}
?>
