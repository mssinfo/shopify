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
@section('scripts')
    @parent
    <script type="text/javascript" data-turbolinks-eval="false">
        let pricingPlan = {!! json_encode(config('msdev2.plan')) !!};
        let classList = ['twelve','six','four','three']
        let el = [];
        let column = classList[pricingPlan.length-1];
        pricingPlan.forEach(element => {
            let iType = ''
            if(element.interval == "EVERY_30_DAYS"){
                iType = '/month'
            }
            else if(element.interval == "ANNUAL"){
                iType = '/year'
            }
            else if(element.interval == "ONE_TIME"){
                iType = ' One Time'
            }
            let feature = [];
            (element.feature).forEach(element => {
                let cls = ''
                if(element.value=='true'){
                    cls = 'success'
                }else if(element.value=='false'){
                    cls = 'error'
                }
                feature.push(`<tr class="${cls}"><td>
                    <div>
                        ${element.name}
                        ${element.help_text ? ' &nbsp; <span class="tip" data-hover="'+element.help_text+'" style="position: relative;top: -5px;"><i class="icon-question"></i></span>' : ''}
                    </div>
                </td></tr>`)
            });
            el.push(`<div class="columns ${column}"><div class="card">
                <table><thead><tr><th>
                    <div>${element.chargeName}</div>
                    <h2>${element.amount == 0 ? '' : element.currencyCode}${element.amount == 0 ? 'Free' : element.amount }${iType}</h2>
                </th></tr></thead>
                <tbody>
                    ${feature.join('')}
                </tbody>
                <tfoot><tr><td><form method="post" action="{{route('msdev2.shopify.plan.subscribe')}}">
                    <button>Purchase</button>
                </form></td></tr></tfoot>
                </table>
            </div></div>`)
        });
        document.querySelector('.plist').innerHTML = el.join('')
        actions.TitleBar.create(app, { title: 'Plan List' });
    </script>
@endsection
