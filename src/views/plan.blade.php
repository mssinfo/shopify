@extends('msdev2::layout.master')
@section('content')
<header>
    <h1>Pricing Plan</h1>
    <h2>Select package which you want to select</h2>
</header>
<section>
    <article class="plist">

    </article>
</section>
@endsection
@section('styles')
<style>
.card.active,.card:hover {
    transform: scale(1.05);
}
</style>
@endsection
@section('scripts')
    @parent
    <script type="text/javascript">
        var currentPlan = '{{$activePlanName}}';
        var appUsed = parseInt('{{$appUsed}}');
        var pricingPlan = {!! json_encode(config('msdev2.plan')) !!};
        var classList = ['twelve','six','four','three']
        var el = [];
        var column = classList[pricingPlan.length-1];
        pricingPlan.forEach((element,key) => {
            let iType = ''
            if(parseInt(element.amount) == 0){
                iType = ''
            }
            else if(element.interval == "EVERY_30_DAYS"){
                iType = '/month'
            }
            else if(element.interval == "ANNUAL"){
                iType = '/year'
            }
            else if(element.interval == "ONE_TIME"){
                iType = ' One Time'
            }
            let properties = [];
            (element.properties).forEach(element => {
                let cls = ''
                if(element.value=='true'){
                    cls = 'success'
                }else if(element.value=='false'){
                    cls = 'error'
                }
                properties.push(`<tr class="${cls}"><td>
                    <div>${element.value=='true' ? '✅' : '❌ '}
                        ${element.name}
                        ${element.help_text ? ' &nbsp; <span class="tip" data-hover="'+element.help_text+'" style="position: relative;top: -5px;"><i class="icon-question"></i></span>' : ''}
                    </div>
                </td></tr>`)
            });
            let enable = ''; 
            if(typeof element.feature.plan != "undefined"){
                enable = 'disabled="disabled" readonly="readonly"';
                if(element.feature.plan == 'all' || element.feature.plan == '{{\Msdev2\Shopify\Utils::$shop->detail["plan_name"]}}' || (element.feature.plan == 'basic' && '{{\Msdev2\Shopify\Utils::$shop->detail["plan_name"]}}' == 'partner_test')){
                    enable = '';
                }
            }
            let buttonForm = `<button type="button" disabled="disabled">Current Plan</button>`;
            let isActive = 'active'
            if(currentPlan != element.chargeName){
                isActive = ''
                buttonForm = `<form target="_parent" method="post" action="{{mRoute('msdev2.shopify.plan.subscribe')}}" ${enable} >
                    <input type="hidden" name="plan" value="${element.chargeName}">`;
                if(appUsed < element.trialDays && element.trialDays > 0){
                    buttonForm += `<button ${enable}>${'Start '+ (element.trialDays-appUsed) +' Day Trial'}</button>`
                }else{
                    buttonForm += `<button ${enable}>Purchase</button>`
                }
                buttonForm += `</form>`;
            }
            el.push(`<div class="columns ${column}"><div class="card ${isActive}">
                <table><thead><tr><th>
                    <div>${element.chargeName}</div>
                    <h2>${element.amount == 0 ? '' : element.currencyCode}${element.amount == 0 ? 'Free' : element.amount }${iType}</h2>
                </th></tr></thead>
                <tbody>
                    ${properties.join('')}
                </tbody>
                <tfoot><tr><td>
                    ${buttonForm}
                </td></tr></tfoot>
                </table>
            </div></div>`)
        });
        document.querySelector('.plist').innerHTML = el.join('')
        // actions.TitleBar.create(app, { title: 'Plan List' });
    </script>
@endsection
