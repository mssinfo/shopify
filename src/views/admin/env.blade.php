@extends('msdev2::layout.admin')

@section('content')
<div class="container-fluid px-0">
    <h4 class="mb-3">System Configuration</h4>

    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <div>
            <strong>Warning:</strong> Editing the <code>.env</code> file incorrectly can break your application immediately. Ensure you have a backup before saving.
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-terminal me-2"></i> .env Editor</span>
        </div>
        <div class="card-body p-0">
            <form action="{{ route('admin.env.update') }}" method="POST">
                @csrf
                <textarea name="env_content" class="form-control font-monospace border-0 p-3" rows="25" style="background: #1e1e1e; color: #00ff9d; resize: vertical;">{{ $content }}</textarea>
                
                <div class="p-3 bg-light border-top text-end">
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure? This will overwrite your .env file.')">
                        <i class="fas fa-save me-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection