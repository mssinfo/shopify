@extends('msdev2::layout.master')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Satisfy&display=swap" rel="stylesheet">
<style>
.planInfoItem {
    display: none;
}
</style>
<div class="plan_page">
    @if (config('msdev2.plan_offer.enable'))
    <div class="email-banner">
        <div class="banner_title">
            <div class="banner_info">
                <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg" focusable="false" aria-hidden="true" style="
                width: 20px;
                "><path d="M11 6.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"></path><path d="M10.75 9.25a.75.75 0 0 0-1.5 0v4.5a.75.75 0 0 0 1.5 0v-4.5Z"></path><path fill-rule="evenodd" d="M10 17a7 7 0 1 0 0-14 7 7 0 0 0 0 14Zm0-1.5a5.5 5.5 0 1 0 0-11 5.5 5.5 0 0 0 0 11Z"></path></svg>
                <p class="banner_message">{{config('msdev2.plan_offer.heading')}}</p>
            </div>
            <button class=" close-email-banner close_banner"><img class="banner_icon " src="images/close.png" alt=""></button>
        </div>
        <div class="banner_content">
            {!!config('msdev2.plan_offer.detail')!!}
        </div>
    </div>
    @endif
    <div class="planContentMain">
        <div class="planContentWrap">
            <div class="selectPlans">
                <div class="selectPlanBox">
                    <div class="planBoxItemLeft">
                        <div class="plansText">
                            Plans
                        </div>
                    </div>
                    <div class="planBoxItemRight">
                        <div class="planstabs">
                            @if (isset($hasPlans["ONE_TIME"]))
                            <div class="planstabsItem active" data-plan="ONE_TIME">
                                <div class="planTabBtn">
                                    <span class="plantbText">One Time</span>
                                </div>
                            </div>
                            @endif
                            @if (isset($hasPlans["EVERY_30_DAYS"]))
                            <div class="planstabsItem @if (!iset($hasPlans["ONE_TIME"])) active @endif" data-plan="EVERY_30_DAYS">
                                <div class="planTabBtn">
                                    <span class="plantbText">OneTime</span>
                                </div>
                            </div>
                            @endif
                            @if (isset($hasPlans["ANNUAL"]))
                            <div class="planstabsItem" data-plan="ANNUAL">
                                <div class="planTabBtn">
                                    <span class="plantbText">
                                        <div class="annualPlanCnt">
                                            <div class="annualText">
                                                <span class="annualPlnTxt">Annually</span>
                                                @if (config('msdev2.plan_offer.yearly.info') != '')
                                                <span class="annualPlanSaveText">{{config('msdev2.plan_offer.yearly.info')}}</span>
                                                @endif
                                            </div>
                                            @if (config('msdev2.plan_offer.yearly.offer') != '')
                                            <div class="stackItem">
                                                <span class="getfreetext">{{config('msdev2.plan_offer.yearly.offer')}}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        <div class="customPlanContent">
        <div class="cstmPlnCntIn">
            <div class="ResourceFeature">
                <div class="plan__price">
                </div>
                <div class="ResourceListCnt">
                    <ul class="ResourceList">
                        @foreach ($properties as $name=>$property)
                            <li><div class="rsListTitle"><span class="rsListTitleItem">{{$name}}
                                @if (isset($property["help_text"]) && $property["help_text"] !="")
                                &nbsp; <span class="tip" data-hover="{{$property["help_text"]}}" style="position: relative;top: -5px; left: 10px;"><i class="icon-question"></i></span>
                                @endif
                            </span></div></li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="PlanInfoCnt">
                <div class="PlanInfoCntIn">
                    @foreach ($plans as $plan)
                    <div class="planInfoItem" data-type="{{$plan["interval"]}}">
                        <div class="planInfoHeadCnt plan__price">
                            @if ($plan["amount"] == 0)
                            <span class="planHeadTopCnt">{{$plan["chargeName"]}}</span>
                            @else
                            <div class="priceOfmnth">
                                <span class="proText">{{$plan["chargeName"]}}</span>
                                <div class="priceOfmnthText">
                                    <p>{{$plan["currencyCode"]}}{{$plan["amount"]}}<span class="plnMnttext">
                                    @if ($plan["interval"] == "EVERY_30_DAYS") /mo @elseif ($plan["interval"] == "ANNUAL") /year @else once @endif    
                                    </span></p>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="planInfoListCnt">
                            <ul class="planInfoList">
                                @foreach ($properties as $name=>$property)
                                <li>
                                    <div class="planInfoDes">
                                        @if (isset($plan["properties"][$name]))
                                            @if ($plan["properties"][$name]["value"] == "true")
                                                <svg class="planIcon__Svg" focusable="false" aria-hidden="true" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" bis_size="{&quot;x&quot;:745,&quot;y&quot;:459,&quot;w&quot;:20,&quot;h&quot;:20,&quot;abs_x&quot;:985,&quot;abs_y&quot;:559}"><path d="m7.293 14.707-3-3a.999.999 0 1 1 1.414-1.414l2.236 2.236 6.298-7.18a.999.999 0 1 1 1.518 1.3l-7 8a1 1 0 0 1-.72.35 1.017 1.017 0 0 1-.746-.292z" bis_size="{&quot;x&quot;:749,&quot;y&quot;:464,&quot;w&quot;:11,&quot;h&quot;:10,&quot;abs_x&quot;:989,&quot;abs_y&quot;:564}"></path></svg>
                                            @elseif($plan["properties"][$name]["value"] == "false")    
                                                <svg class="Polaris-Icon__Svg" focusable="false" aria-hidden="true" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" bis_size="{&quot;x&quot;:745,&quot;y&quot;:2871,&quot;w&quot;:20,&quot;h&quot;:20,&quot;abs_x&quot;:985,&quot;abs_y&quot;:2971}"><path d="M15 9H5a1 1 0 1 0 0 2h10a1 1 0 1 0 0-2z" bis_size="{&quot;x&quot;:749,&quot;y&quot;:2880,&quot;w&quot;:12,&quot;h&quot;:2,&quot;abs_x&quot;:989,&quot;abs_y&quot;:2980}"></path></svg>
                                            @else
                                                {{$plan["properties"][$name]["value"]}}
                                            @endif
                                        @else
                                        <svg class="Polaris-Icon__Svg" focusable="false" aria-hidden="true" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" bis_size="{&quot;x&quot;:745,&quot;y&quot;:2871,&quot;w&quot;:20,&quot;h&quot;:20,&quot;abs_x&quot;:985,&quot;abs_y&quot;:2971}"><path d="M15 9H5a1 1 0 1 0 0 2h10a1 1 0 1 0 0-2z" bis_size="{&quot;x&quot;:749,&quot;y&quot;:2880,&quot;w&quot;:12,&quot;h&quot;:2,&quot;abs_x&quot;:989,&quot;abs_y&quot;:2980}"></path></svg>
                                        @endif
                                    
                                    </div>
                                </li>
                                @endforeach
                                <li>
                                    <div class="planBtns">
                                        
                                        @if ($activePlanName == $plan["chargeName"])
                                            <button class="choosePlanBtn" disabled="disabled" type="button"><span class="choosePlanBtnText">Current Plan</span></button>
                                        @elseif(!$plan["enable"])
                                            <button class="choosePlanBtn" disabled="disabled">Purchase</button>
                                        @elseif($appUsed < $plan["trialDays"] && $plan["trialDays"] > 0)
                                        <form target="_parent" method="post" action="{{mRoute('msdev2.shopify.plan.subscribe')}}" >
                                            <input type="hidden" name="plan" value="{{$plan["chargeName"]}}">
                                            <button class="choosePlanBtn" ${enable}>Start {{$plan["trialDays"]-$appUsed}} Day Trial</button>
                                        </form>    
                                        @else
                                        <form target="_parent" method="post" action="{{mRoute('msdev2.shopify.plan.subscribe')}}" >
                                            <input type="hidden" name="plan" value="{{$plan["chargeName"]}}">
                                            <button class="choosePlanBtn">Purchase</button>
                                        </form>    
                                        @endif
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
getActive()
function getActive(){
    let nodeList = document.querySelectorAll('.planInfoItem')
    for (let i = 0; i < nodeList.length; i++) {
        nodeList[i].style.display = "none";
    }
    let selectedPlan = document.querySelector('.planstabsItem.active').getAttribute('data-plan')
    nodeList = document.querySelectorAll('div[data-type="'+selectedPlan+'"]')
    for (let i = 0; i < nodeList.length; i++) {
        nodeList[i].style.display = "inline-block";
    }
}
cbox = document.querySelectorAll('.planstabsItem')
for (let i = 0; i < cbox.length; i++) {
     cbox[i].addEventListener("click", function() {
        let nodeList = document.querySelectorAll('.planstabsItem')
        console.log(nodeList,cbox[i])
        for (let i = 0; i < nodeList.length; i++) {
            nodeList[i].classList.remove("active");
        }
       cbox[i].classList.add("active");
       getActive()
     });
 }
</script>
@endsection