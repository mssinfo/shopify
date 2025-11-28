@extends('msdev2::layout.emails')

@section('content')
<h2 style="font-family: arial, helvetica, sans-serif; font-size: 22px; color: #333333; margin-bottom: 20px;">
    New Support Ticket Created
</h2>

<p style="font-family: arial, helvetica, sans-serif; font-size: 16px; color: #555555; line-height: 1.6;">
    A new ticket has been submitted by <strong>{{ $ticket->shop->shop ?? 'Unknown Shop' }}</strong>.
</p>

<table width="100%" cellpadding="10" cellspacing="0" border="0" style="border: 1px solid #eeeeee; margin: 20px 0;">
    <tr style="background-color: #f9f9f9;">
        <td style="font-weight: bold; width: 30%;">Subject:</td>
        <td>{{ $ticket->subject }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold;">Email:</td>
        <td>{{ $ticket->email }}</td>
    </tr>
    <tr style="background-color: #f9f9f9;">
        <td style="font-weight: bold;">Priority:</td>
        <td>
            @if($ticket->priority == 3) <span style="color: red;">High</span>
            @elseif($ticket->priority == 2) <span style="color: orange;">Medium</span>
            @else <span style="color: green;">Low</span> @endif
        </td>
    </tr>
    <tr>
        <td style="font-weight: bold;">Message:</td>
        <td>{{ Str::limit($ticket->detail, 200) }}</td>
    </tr>
</table>

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn-primary" style="color: #ffffff;">
        Reply in Admin Panel
    </a>
</div>
@endsection