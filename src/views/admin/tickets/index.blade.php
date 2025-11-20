@extends('msdev2::layout.admin')

@section('content')
<div class="container-fluid px-0">
    <h4 class="mb-3">Support Tickets</h4>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Subject / Category</th>
                        <th>Shop / Email</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $ticket)
                    <tr>
                        <td><span class="text-muted">#{{ $ticket->id }}</span></td>
                        <td>
                            <div class="fw-bold text-truncate" style="max-width: 250px;">{{ $ticket->subject }}</div>
                            <span class="badge bg-light text-secondary border">{{ $ticket->category }}</span>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $ticket->shop->shop ?? 'Uninstalled' }}</div>
                            <div class="small text-muted">{{ $ticket->email }}</div>
                        </td>
                        <td>
                            @if($ticket->priority == 3) <span class="badge bg-danger">High</span>
                            @elseif($ticket->priority == 2) <span class="badge bg-warning text-dark">Medium</span>
                            @else <span class="badge bg-secondary">Low</span>
                            @endif
                        </td>
                        <td>
                            @if($ticket->status == 0) <span class="badge bg-danger">Open</span>
                            @elseif($ticket->status == 1) <span class="badge bg-warning text-dark">In Progress</span>
                            @else <span class="badge bg-success">Closed</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $ticket->created_at->diffForHumans() }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No tickets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $tickets->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection