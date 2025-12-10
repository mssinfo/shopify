@extends('msdev2::layout.admin')

@section('content')
<div class="container-fluid px-0">
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Email Marketing</h4>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <i class="fas fa-paper-plane text-primary me-2"></i> Create Broadcast
                </div>
                <div class="card-body p-4">
                    
                    <form action="{{ route('admin.marketing.send') }}" method="POST" id="marketingForm">
                        @csrf

                        <!-- 1. Template Selector -->
                        <div class="mb-4 bg-light p-3 rounded border">
                            <label class="form-label fw-bold text-primary">Choose a Template</label>
                            <select id="templateSelect" class="form-select">
                                <option value="" selected>-- Select to Auto-fill --</option>
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl['id'] }}">{{ $tpl['name'] }}</option>
                                @endforeach
                                <option value="custom">Custom / Blank</option>
                            </select>
                        </div>

                        <!-- 2. Audience Selection (Existing Code) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Select Audience</label>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="form-check p-3 border rounded bg-white">
                                        <input class="form-check-input" type="radio" name="target" id="t1" value="all_active" checked onchange="toggleFilters()">
                                        <label class="form-check-label w-100" for="t1">All Active</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check p-3 border rounded bg-white">
                                        <input class="form-check-input" type="radio" name="target" id="t2" value="plan" onchange="toggleFilters()">
                                        <label class="form-check-label w-100" for="t2">By Plan</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check p-3 border rounded bg-white">
                                        <input class="form-check-input" type="radio" name="target" id="t3" value="uninstalled" onchange="toggleFilters()">
                                        <label class="form-check-label w-100" for="t3">Uninstalled</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check p-3 border rounded bg-white">
                                        <input class="form-check-input" type="radio" name="target" id="t4" value="specific" onchange="toggleFilters()">
                                        <label class="form-check-label w-100" for="t4">Specific</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Filter: Plan Selection -->
                            <div class="mt-3 d-none" id="planFilter">
                                <label class="small text-muted">Select Plan</label>
                                <select name="plan_name" class="form-select">
                                    <option value="freemium">Freemium / No Plan</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan }}">{{ $plan }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filter: Specific Shops -->
                            <div class="mt-3 d-none" id="specificFilter">
                                <label class="small text-muted">Enter Shop Domains (Comma separated)</label>
                                <input type="text" name="specific_shops" class="form-control" placeholder="shop1.myshopify.com, shop2.myshopify.com">
                            </div>
                        </div>

                        <hr>

                        <!-- 3. Email Content -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subject Line</label>
                            <input type="text" name="subject" id="subjectInput" class="form-control" placeholder="Enter email subject" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Message Body</label>
                            <textarea name="message" id="messageInput" class="form-control" rows="10" placeholder="Write your message here..." required></textarea>
                            <div class="form-text">Content supports basic text formatting.</div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn btn-outline-dark px-4 me-2" onclick="openPreview()">
                                    <i class="fas fa-eye me-2"></i> Preview Email
                                </button>
                                <button type="button" class="btn btn-outline-secondary px-4" data-bs-toggle="modal" data-bs-target="#exportConfirmModal">
                                    <i class="fas fa-file-export me-2"></i> Export Emails
                                </button>
                            </div>
                            
                            <button type="button" class="btn btn-success px-5" data-bs-toggle="modal" data-bs-target="#sendConfirmModal">
                                <i class="fas fa-paper-plane me-2"></i> Send Emails
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PREVIEW MODAL -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i> Email Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Iframe to isolate styles -->
                <iframe id="previewFrame" style="width: 100%; height: 600px; border: none;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- EXPORT CONFIRMATION MODAL -->
<div class="modal fade" id="exportConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-export me-2"></i> Export Email List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to export the email list for the selected audience?</p>
                <p class="text-muted small mb-0">This will download a CSV file with all matching shop emails.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('exportForm').submit()">
                    <i class="fas fa-download me-2"></i> Export
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SEND CONFIRMATION MODAL -->
<div class="modal fade" id="sendConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i> Send Email Campaign</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Ready to send this email to the selected audience?</strong></p>
                <p class="text-muted small mb-0">This action cannot be undone. Make sure you've previewed the email and selected the correct audience.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="document.getElementById('marketingForm').submit()">
                    <i class="fas fa-paper-plane me-2"></i> Send Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Export Form -->
<form id="exportForm" action="{{ route('admin.marketing.export') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="target" id="export_target">
    <input type="hidden" name="plan_name" id="export_plan_name">
    <input type="hidden" name="specific_shops" id="export_specific_shops">
</form>

@push('scripts')
<script>
    // 1. Pass PHP Templates array to JS
    const templates = @json($templates);

    // 2. Handle Template Selection
    document.getElementById('templateSelect').addEventListener('change', function() {
        const selectedId = this.value;
        const subjectInput = document.getElementById('subjectInput');
        const messageInput = document.getElementById('messageInput');

        if (selectedId === 'custom' || selectedId === '') {
            subjectInput.value = '';
            messageInput.value = '';
            return;
        }

        const template = templates.find(t => t.id === selectedId);
        if (template) {
            subjectInput.value = template.subject;
            messageInput.value = template.content;
        }
    });

    // 3. Filter Toggling
    function toggleFilters() {
        const target = document.querySelector('input[name="target"]:checked').value;
        document.getElementById('planFilter').classList.add('d-none');
        document.getElementById('specificFilter').classList.add('d-none');

        if (target === 'plan') {
            document.getElementById('planFilter').classList.remove('d-none');
        } else if (target === 'specific') {
            document.getElementById('specificFilter').classList.remove('d-none');
        }
    }

    // 4. Sync Export Form Data
    // When export modal is shown, copy the form data to the hidden export form
    document.getElementById('exportConfirmModal').addEventListener('show.bs.modal', function() {
        const target = document.querySelector('input[name="target"]:checked').value;
        const planName = document.querySelector('select[name="plan_name"]').value;
        const specificShops = document.querySelector('input[name="specific_shops"]').value;
        
        document.getElementById('export_target').value = target;
        document.getElementById('export_plan_name').value = planName;
        document.getElementById('export_specific_shops').value = specificShops;
    });

    // 5. Live Preview Logic
    function openPreview() {
        const subject = document.getElementById('subjectInput').value;
        const message = document.getElementById('messageInput').value;
        const csrfToken = document.querySelector('input[name="_token"]').value;

        if (!subject || !message) {
             $GLOBALS.showToast("Please enter a subject and message to preview.", true);
            return;
        }

        // Show loading state could be good here
        
        fetch("{{ route('admin.marketing.preview') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json"
            },
            body: JSON.stringify({ subject: subject, message: message })
        })
        .then(response => response.json())
        .then(data => {
            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            const iframe = document.getElementById('previewFrame');
            
            // Write HTML into the iframe
            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(data.html);
            doc.close();

            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            $GLOBALS.showToast("Failed to generate preview.", true);
        });
    }

</script>
@endpush
@endsection