@extends('msdev2::layout.agent')
@section('content')
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Ticket</th>
            <th>Category</th>
            <th>Created At</th>
            <th width="150px;">Action</th>
        </tr>
    </thead>
    <tbody>
        @if(!empty($tickets) && $tickets->count())
            @foreach($tickets as $ticket)
                <tr class="table-{{$ticket->priority==4 ? 'danger' : ($ticket->priority == 3 ? 'warning' : ($ticket->priority == 2 ? 'light' : 'default'))}}">

                    <td>{{ $ticket->shop->shop }}@if ($ticket->password != "")
                        <strong>(Pass : {{$ticket->password}})</strong>
                    @endif - {{ $ticket->email }}
                    <br>
                        Subject : {{ $ticket->subject }}</td>
                    <td>{{ $ticket->category }}</td>
                    <td>{{ $ticket->created_at }}</td>
                    <td>
                        <div class="d-flex gap-1 justify-content-end">
                            <button class="btn btn-sm btn-outline-secondary toggle-ticket-details" data-ticket-id="{{ $ticket->id }}">Toggle</button>
                            <a class="btn btn-info" href="{{route('msdev2.agent.ticket.resolve',['id'=>$ticket->id])}}">Resolved</a>
                        @if ($ticket->files != "")
                            <a href="{{asset("storage".$ticket->files)}}" download target="_blank"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/>
                              </svg></a>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="4">
                       Detail :  {{$ticket->detail}}
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="10">There are no data.</td>
            </tr>
        @endif
    </tbody>
</table>

{!! $tickets->links() !!}
<br>
<br>
<br>
<br>
@endsection