<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f6f6f7; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { width: 100%; max-width: 400px; border: 1px solid #e1e3e5; border-radius: 8px; background: white; padding: 30px; }
        .btn-primary { background: #008060; border: none; }
        .btn-primary:hover { background: #006e52; }
    </style>
</head>
<body>
    <div class="login-card">
        <h4 class="mb-4 text-center"><i class="fas fa-shield-alt"></i> Admin Mode</h4>
        <form method="POST" action="{{ route('msdev2.admin.login.submit') }}">
            @csrf
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>
</html>