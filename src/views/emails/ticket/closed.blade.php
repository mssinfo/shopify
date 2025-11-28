@extends('msdev2::layout.emails')

@section('content')
<h2 style="font-family: arial, helvetica, sans-serif; font-size: 22px; color: #333333; margin-bottom: 20px;">
    Ticket Closed: #{{ $ticket->id }}
</h2>

<p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6;">
    Hello,
</p>

<p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6;">
    This email is to confirm that your support ticket <strong>"{{ $ticket->subject }}"</strong> has been marked as <strong>Resolved</strong>.
</p>

<div style="text-align: center; margin: 30px 0;">
    <img src="https://cdn-icons-png.flaticon.com/512/190/190411.png" width="64" alt="Success" style="display:block; margin: 0 auto 10px;">
    <span style="font-size: 18px; color: #008060; font-weight: bold;">Issue Resolved</span>
</div>

<p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6;">
    If you are still experiencing issues regarding this topic, please reply to this email or open a new ticket from your dashboard.
</p>

<p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6;">
    Thank you for using {{ config('app.name') }}!
</p>
@endsection