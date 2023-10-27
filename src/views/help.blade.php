@extends('msdev2::layout.master')
@section('content')
<span class="toc-block" id="home">
    <header>
        <h1>Help & Support</h1>
        <h2>Need help with the app?</h2>
    </header>
    <section>
        <div class="column">
            <article class="margin-0">
                <div class="card columns twelve">
                    <h2>Contact our support</h2>
                    <p>Need Assistance? Our Live Chat Support is ready to assist you in real-time. Connect with us now and experience speedy solutions to all your questions and concerns!</p>
                </div>
            </article>
        </div>
    </section>
    <section>
        <div class="column">
            <div class="alert">
                <dl>
                    <dt>Rate our app</dt>
                    <dd>We want to hear from you! Share your experience today and let's create something extraordinary together!
                    </dd>
                </dl>
                @if (config('msdev2.shopify_app_url') != '')
                <a href="{{config('msdev2.shopify_app_url')}}" target="_blank" class="button" control-id="ControlID-55">Rate</a>
                @endif
            </div>
        </div>
    </section>
    <section>
        <div class="column">
            <article class="margin-0">
                <div class="card columns four" id="liveChat" style="display: none">
                    <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 149.5 131.14"><title>source</title><rect x="72.5" y="83.95" width="4.5" height="21.88" fill="#919eab"/><path d="M90.54,132.48H63V104.91H90.54v27.57ZM67.47,128H86V109.41H67.47V128Z" transform="translate(-2 -1.34)" fill="#919eab"/><rect x="69.75" y="115.11" width="10" height="4.5" fill="#919eab"/><path d="M151.5,120.94H123.93V93.37H151.5v27.57Zm-23.07-4.5H147V97.87H128.43v18.57Z" transform="translate(-2 -1.34)" fill="#919eab"/><rect x="130.72" y="103.57" width="10" height="4.5" fill="#919eab"/><polygon points="124.18 108.07 102.98 108.07 102.98 83.94 107.48 83.94 107.48 103.57 124.18 103.57 124.18 108.07" fill="#919eab"/><path d="M29.57,120.94H2V93.37H29.57v27.57ZM6.5,116.44H25.07V97.87H6.5v18.57Z" transform="translate(-2 -1.34)" fill="#919eab"/><rect x="8.79" y="103.57" width="10" height="4.5" fill="#919eab"/><polygon points="46.52 108.07 25.32 108.07 25.32 103.57 42.02 103.57 42.02 83.94 46.52 83.94 46.52 108.07" fill="#919eab"/><path d="M128.43,87.53H25.07V17.34H128.43V87.53ZM29.57,83h94.36V21.84H29.57V83Z" transform="translate(-2 -1.34)" fill="#919eab"/><path d="M128.43,21.84H25.07V1.34H128.43V21.84Zm-98.86-4.5h94.36V5.84H29.57V17.34Z" transform="translate(-2 -1.34)" fill="#919eab"/><rect x="34.07" y="8" width="4.5" height="4.5" fill="#919eab"/><rect x="43.06" y="8" width="4.5" height="4.5" fill="#919eab"/><rect x="52.06" y="8" width="4.5" height="4.5" fill="#919eab"/><polygon points="64.7 59.58 55.05 49.92 64.7 40.27 67.89 43.45 61.42 49.92 67.89 56.39 64.7 59.58" fill="#919eab"/><polygon points="84.79 59.58 81.61 56.39 88.08 49.92 81.61 43.45 84.79 40.27 94.44 49.92 84.79 59.58" fill="#919eab"/><rect x="63.25" y="49.01" width="27" height="4.5" transform="translate(4.48 109.77) rotate(-74.16)" fill="#919eab"/></svg>

                    <h5>Live Chat</h5>

                    <p>Need Assistance? Our Live Chat Support is ready.</p>
                        <a href="#" class="button download twak_chat disabled" id="tawk_chat_button">Chat Now</a>
                    </p>
                </div>
                <div class="card columns four" id="liveSupport">
                    <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 149.5 131.14"><title>source</title><rect x="72.5" y="83.95" width="4.5" height="21.88" fill="#919eab"/><path d="M90.54,132.48H63V104.91H90.54v27.57ZM67.47,128H86V109.41H67.47V128Z" transform="translate(-2 -1.34)" fill="#919eab"/><rect x="69.75" y="115.11" width="10" height="4.5" fill="#919eab"/><path d="M151.5,120.94H123.93V93.37H151.5v27.57Zm-23.07-4.5H147V97.87H128.43v18.57Z" transform="translate(-2 -1.34)" fill="#919eab"/><rect x="130.72" y="103.57" width="10" height="4.5" fill="#919eab"/><polygon points="124.18 108.07 102.98 108.07 102.98 83.94 107.48 83.94 107.48 103.57 124.18 103.57 124.18 108.07" fill="#919eab"/><path d="M29.57,120.94H2V93.37H29.57v27.57ZM6.5,116.44H25.07V97.87H6.5v18.57Z" transform="translate(-2 -1.34)" fill="#919eab"/><rect x="8.79" y="103.57" width="10" height="4.5" fill="#919eab"/><polygon points="46.52 108.07 25.32 108.07 25.32 103.57 42.02 103.57 42.02 83.94 46.52 83.94 46.52 108.07" fill="#919eab"/><path d="M128.43,87.53H25.07V17.34H128.43V87.53ZM29.57,83h94.36V21.84H29.57V83Z" transform="translate(-2 -1.34)" fill="#919eab"/><path d="M128.43,21.84H25.07V1.34H128.43V21.84Zm-98.86-4.5h94.36V5.84H29.57V17.34Z" transform="translate(-2 -1.34)" fill="#919eab"/><rect x="34.07" y="8" width="4.5" height="4.5" fill="#919eab"/><rect x="43.06" y="8" width="4.5" height="4.5" fill="#919eab"/><rect x="52.06" y="8" width="4.5" height="4.5" fill="#919eab"/><polygon points="64.7 59.58 55.05 49.92 64.7 40.27 67.89 43.45 61.42 49.92 67.89 56.39 64.7 59.58" fill="#919eab"/><polygon points="84.79 59.58 81.61 56.39 88.08 49.92 81.61 43.45 84.79 40.27 94.44 49.92 84.79 59.58" fill="#919eab"/><rect x="63.25" y="49.01" width="27" height="4.5" transform="translate(4.48 109.77) rotate(-74.16)" fill="#919eab"/></svg>

                    <h5>Quick Support</h5>

                    <p>Need Assistance? Our Quick Support is ready.</p>
                        <a href="mailto:{{config('msdev2.contact_email')}}" target="_blank" class="button download" >Mail us</a>
                    </p>
                </div>
                <div class="card columns four">
                    <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 106.9 124.5"><title>css</title><path d="M47.48,6.32H20.79V109.74h-4.5V1.82h92.58V109.74A16.59,16.59,0,0,1,92.3,126.32v-4.5a12.09,12.09,0,0,0,12.07-12.08V6.32H47.48Z" transform="translate(-1.97 -1.82)" fill="#919eab"/><path d="M92.29,126.32H18.54A16.6,16.6,0,0,1,2,109.75V107.5H80.22v2.25a12.09,12.09,0,0,0,12.08,12.08v4.5ZM6.68,112a12.1,12.1,0,0,0,11.87,9.83H81A16.56,16.56,0,0,1,75.87,112H6.68Z" transform="translate(-1.97 -1.82)" fill="#919eab"/><rect x="27.96" y="14.03" width="23.99" height="4.5" fill="#919eab"/><rect x="27.96" y="22.75" width="15.24" height="4.5" fill="#919eab"/><rect x="27.96" y="31.46" width="23.99" height="4.5" fill="#919eab"/><rect x="27.96" y="40.18" width="10.74" height="4.5" fill="#919eab"/><rect x="27.96" y="48.89" width="34.74" height="4.5" fill="#919eab"/><rect x="27.96" y="57.61" width="44.49" height="4.5" fill="#919eab"/><rect x="27.96" y="66.32" width="23.99" height="4.5" fill="#919eab"/><rect x="27.96" y="75.04" width="27.99" height="4.5" fill="#919eab"/><rect x="27.96" y="83.79" width="59.74" height="4.5" fill="#919eab"/><rect x="27.96" y="92.54" width="36.74" height="4.5" fill="#919eab"/></svg>

                    <h5>Submit Ticket</h5>

                    <p>Get any issue? send a ticket to our expert.</p>

                    <p><a href="{{ mRoute('msdev2.shopify.ticket') }}" class="button download">Create</a></p>
                </div>
                <div class="card columns four">
                    <svg fill="#000000" version="1.1" viewBox="0 0 337.56 337.56" xml:space="preserve" xmlns="http://www.w3.org/2000/svg"><path d="m337.56 67.704v-28.33c0-17.506-14.242-31.748-31.748-31.748h-54.572c-4.932-3.021-10.727-4.765-16.922-4.765h-201.82c-17.92-1e-3 -32.5 14.579-32.5 32.499v266.84c0 17.921 14.58 32.5 32.5 32.5h201.82c6.196 0 11.992-1.745 16.925-4.767h54.569c17.506 0 31.748-14.242 31.748-31.748v-28.33c0-9.715-4.391-18.42-11.287-24.248 6.896-5.828 11.287-14.533 11.287-24.248v-28.331c0-9.715-4.391-18.42-11.287-24.248 6.896-5.828 11.287-14.533 11.287-24.248v-28.33c0-9.715-4.391-18.42-11.287-24.248 6.897-5.829 11.288-14.534 11.288-24.248zm-85.743 234.49c0 9.649-7.851 17.5-17.5 17.5h-201.82c-9.649 0-17.5-7.851-17.5-17.5v-266.84c0-9.649 7.851-17.5 17.5-17.5h201.82c9.649 0 17.5 7.851 17.5 17.5v266.84zm70.743-4.014c0 9.235-7.513 16.748-16.748 16.748h-41.595c1.673-3.912 2.601-8.216 2.601-12.733v-49.093h38.994c9.235 0 16.748 7.513 16.748 16.748v28.33zm0-76.827c0 9.235-7.513 16.748-16.748 16.748h-38.994v-61.827h38.994c9.235 0 16.748 7.513 16.748 16.748v28.331zm0-76.827c0 9.235-7.513 16.748-16.748 16.748h-38.994v-61.827h38.994c9.235 0 16.748 7.513 16.748 16.748v28.331zm0-76.826c0 9.235-7.513 16.748-16.748 16.748h-38.994v-49.092c0-4.518-0.929-8.822-2.602-12.735h41.596c9.235 0 16.748 7.513 16.748 16.748v28.331z"/><rect x="40.413" y="230.02" width="185.99" height="15"/><path d="m66.891 206.2h133.04c2.263 0 4.405-1.021 5.829-2.78s1.978-4.066 1.507-6.279c-3.595-16.907-13.071-32.176-26.474-43.02 8.782-10.818 13.689-24.438 13.689-38.522 0-33.674-27.396-61.07-61.07-61.07s-61.07 27.396-61.07 61.07c0 14.084 4.908 27.704 13.689 38.522-13.402 10.844-22.878 26.112-26.472 43.02-0.471 2.213 0.083 4.521 1.507 6.279 1.425 1.759 3.567 2.78 5.83 2.78zm34.452-44.617c1.988-1.245 3.279-3.35 3.488-5.687s-0.687-4.637-2.422-6.216c-9.579-8.718-15.072-21.14-15.072-34.081 0-25.403 20.667-46.07 46.07-46.07s46.07 20.667 46.07 46.07c0 12.941-5.494 25.363-15.072 34.081-1.735 1.579-2.631 3.879-2.422 6.216s1.5 4.441 3.488 5.687c11.154 6.989 19.735 17.49 24.42 29.618h-112.97c4.685-12.128 13.266-22.631 24.42-29.618z"/><rect x="63.83" y="259.69" width="139.16" height="15"/></svg>
                    
                    <h5>Contact Us</h5>

                    <p>Need more help? Contact us vue a our contact us page.</p>

                    <p><a href="{{config('msdev2.contact_url')}}" class="button secondary" target="_blank">Contact</a></p>
                </div>
            </article>
        </div>
    </section>

</span>
@endsection
@section('scripts')
@if (config('msdev2.tawk_url') != '')
    <script>
        window.Tawk_API.onLoad = function(){
            var pageStatus = window.Tawk_API.getStatus();
            var btnTwak = document.getElementById('tawk_chat_button')
            var liveChatDiv = document.getElementById('liveChat')
            var liveSupportDiv = document.getElementById('liveSupport')
            if(pageStatus === 'online'){
                liveChatDiv.style.display = 'inline-block'
                liveSupportDiv.remove();
                btnTwak.classList.remove('disabled');
                btnTwak.addEventListener("click", (e) =>{
                    e.preventDefault();
                    window.Tawk_API.maximize();
                    return  false;
                });
            }else{
                liveChatDiv.remove()
                liveSupportDiv.style.display = 'inline-block'
                btnTwak.classList.add('disabled');
            }
        };
    </script>
@endif    
@endsection
@section('styles')
<style>
#home header {
    color: #fff;
    background-repeat: no-repeat;
    background-position: center;
    background-size: cover;
    background-color: #43467f;
}
#home article .card {
    border-top: 0.5rem solid #c4cdd5;
}
#home svg {
    display: inline-block;
    float: right;
    width: 60px;
    height: 60px;
    margin: 0 0 0 1rem;
}
#home h5 {
    margin-bottom: 1.5rem;
}
#home p:last-child {
    margin-bottom: 0;
}
.margin-0{
    margin: 0
}
</style>
@endsection