@extends('msdev2::layout.emails')

@section('content')
<h2 style="font-family: arial, helvetica, sans-serif; font-size: 22px; color: #333333; margin-bottom: 20px;">
    New reply to your ticket
</h2>

<p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6;">
    Hello,
</p>

<p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6;">
    Our support team has replied to your ticket regarding <strong>"{{ $ticket->subject }}"</strong> (Ticket #{{ $ticket->id }}).
</p>

<div style="background-color: #f9f9f9; border-left: 4px solid #008060; padding: 15px; margin: 20px 0; font-family: arial, helvetica, sans-serif; color: #444444; line-height: 1.6;">
    {!! nl2br(e($replyMessage)) !!}
</div>

<p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6;">
    You can view the full conversation or reply directly by clicking the button below.
</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ config('app.url') }}" class="btn-primary" style="color: #ffffff;">
        View Ticket
    </a>
</div>

<p style="font-family: arial, helvetica, sans-serif; font-size: 14px; color: #999999; line-height: 1.4;">
    If you did not create this ticket, please ignore this email.
</p>
@endsection