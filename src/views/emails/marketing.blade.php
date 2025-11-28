@extends('msdev2::layout.emails')

{{-- Optional: Pass 'bannerImage' url from controller for marketing headers --}}

@section('content')

<div style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6; text-align: left;">
    {!! nl2br($content) !!} 
    {{-- Note: Ensure $content is sanitized in controller if allowing HTML, or use e() if plaintext --}}
</div>

<div style="text-align: center; margin: 40px 0;">
    <a href="{{ route('msdev2.install', ["shop" => $shopInfo->shop]) }}" class="btn-primary" style="color: #ffffff;">
        Check it out
    </a>
</div>

<p style="font-family: arial, helvetica, sans-serif; font-size: 13px; color: #999999; text-align: center; margin-top: 30px;">
    You received this email because you have installed {{ config('app.name') }}.
</p>
@endsection