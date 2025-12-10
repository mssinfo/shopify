@extends('msdev2::layout.admin')

@section('content')
<div class="container-fluid px-0">
    
    <!-- Header & Actions -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <a href="{{ route('admin.tickets') }}" class="text-decoration-none text-muted small mb-2 d-block">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <h3 class="mb-1 fw-bold">Ticket #{{ $ticket->id }}</h3>
            <div class="d-flex gap-2 align-items-center">
                @if($ticket->status == 0) <span class="badge bg-danger">Open</span>
                @elseif($ticket->status == 1) <span class="badge bg-warning text-dark">In Progress</span>
                @else <span class="badge bg-success">Closed</span>
                @endif
                <span class="text-muted small">Created {{ $ticket->created_at->format('M d, Y H:i') }}</span>
            </div>
        </div>
        
        <!-- Quick Status Changer -->
        <form action="{{ route('admin.tickets.status', $ticket->id) }}" method="POST" class="d-flex gap-2 align-items-center">
            @csrf
            <select name="status" class="form-select form-select-sm" style="width: 140px;">
                <option value="0" {{ $ticket->status == 0 ? 'selected' : '' }}>Mark Open</option>
                <option value="1" {{ $ticket->status == 1 ? 'selected' : '' }}>Mark In Progress</option>
                <option value="2" {{ $ticket->status == 2 ? 'selected' : '' }}>Mark Closed</option>
            </select>
            <button type="submit" class="btn btn-sm btn-secondary">Update</button>
        </form>
    </div>

    <div class="row g-4">
        
        <!-- LEFT COLUMN: Details & Chat -->
        <div class="col-lg-8">
            
            <!-- Ticket Detail (The Message) -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">
                    Subject: {{ $ticket->subject }}
                </div>
                <div class="card-body">
                    <h6 class="text-muted small text-uppercase mb-2">Description / Detail</h6>
                    <div class="bg-light p-3 rounded border mb-3" style="white-space: pre-wrap;">{{ $ticket->detail }}</div>

                    @if(!empty($files))
                        <h6 class="text-muted small text-uppercase mb-2"><i class="fas fa-paperclip"></i> Attachments</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach($files as $file)
                                <a href="{{ asset(trim($file)) }}" target="_blank" class="btn btn-sm btn-outline-dark d-flex align-items-center gap-2">
                                    <i class="fas fa-file-download"></i> Download File
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Credentials / Password Section -->
            @if(!empty($ticket->password))
            <div class="card border-0 shadow-sm mb-4 border-warning">
                <div class="card-header bg-warning bg-opacity-10 text-dark fw-bold">
                    <i class="fas fa-key me-2"></i> Shared Credentials
                </div>
                <div class="card-body">
                    <label class="form-label small text-muted">Access Password / Details</label>
                    <div class="input-group">
                        <input type="password" class="form-control" value="{{ $ticket->password }}" id="credentialInput" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i> Show
                        </button>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyPassword()">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                    </div>
                </div>
            </div>
            @endif

            <!-- Reply Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Reply to Customer</div>
                <div class="card-body">
                    <form action="{{ route('admin.tickets.reply', $ticket->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <textarea name="message" class="form-control" rows="6" placeholder="Type your response here. An email will be sent to {{ $ticket->email }}"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i> Send Reply & Email
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Meta Data -->
        <div class="col-lg-4">
            
            <!-- Customer Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Customer Information</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between flex-wrap">
                        <span class="text-muted">Shop</span>
                        <strong >
                            <a href="{{ route('admin.shops.show', $ticket->shop_id) }}" class="text-decoration-none">
                                {{ $ticket->shop->domain ?? $ticket->shop->shop }}
                            </a>
                            <a href="{{ route('admin.shops.login', $ticket->shop_id) }}" target="_blank" class="text-decoration-none ms-2">
                                <i class="fas fa-sign-in-alt small"></i>
                            </a>
                            <a href="https://{{ $ticket->shop->domain ?? $ticket->shop->shop }}" target="_blank" class="text-decoration-none ms-2">
                                <i class="fas fa-external-link-alt small"></i>
                            </a>
                        </strong>
                    </li>
                    <li class="list-group-item">
                        <span class="text-muted d-block small">Email</span>
                        <div class="fw-bold text-break">{{ $ticket->email }}</div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">IP Address</span>
                        <span class="font-monospace">{{ $ticket->ip_address ?? 'N/A' }}</span>
                    </li>
                </ul>
            </div>

            <!-- Ticket Meta -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Ticket Metadata</div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Category</span>
                        <span class="badge bg-info text-dark">{{ $ticket->category }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Priority</span>
                        @if($ticket->priority == 3) <span class="badge bg-danger">High</span>
                        @elseif($ticket->priority == 2) <span class="badge bg-warning text-dark">Medium</span>
                        @else <span class="badge bg-secondary">Low</span>
                        @endif
                    </li>
                </ul>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    function togglePassword() {
        const input = document.getElementById('credentialInput');
        const icon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function copyPassword() {
        const input = document.getElementById('credentialInput');
        input.type = 'text'; // Must be text to select
        input.select();
        document.execCommand('copy');
        input.type = 'password'; // Re-hide
        $GLOBALS.showToast("Password copied to clipboard.");
    }
</script>
@endpush
@endsection