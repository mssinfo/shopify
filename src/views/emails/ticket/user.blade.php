@extends('msdev2::layout.emails')

@section('content')
<h2 style="font-family: arial, helvetica, sans-serif; font-size: 22px; color: #333333; margin-bottom: 20px;">
    We received your request (#{{ $ticket->id }})
</h2>

<p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6;">
    Hello,
</p>

<p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6;">
    Thanks for reaching out! We have received your ticket regarding <strong>"{{ $ticket->subject }}"</strong>.
</p>

<p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6;">
    Our support team is reviewing it and will get back to you as soon as possible.
</p>

<div style="background-color: #f1f1f1; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <table width="100%">
        <tr>
            <td width="100" style="font-weight: bold; color: #333;">Ticket ID:</td>
            <td style="color: #555;">#{{ $ticket->id }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; color: #333;">Category:</td>
            <td style="color: #555;">{{ $ticket->category }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; color: #333;">Status:</td>
            <td style="color: #d9534f;">Open</td>
        </tr>
    </table>
</div>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ config('app.url') }}" class="btn-primary" style="color: #ffffff;">
        Go to Dashboard
    </a>
</div>
@endsection