<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { 
            background: #f1f2f4; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }
        .login-card { 
            width: 100%; 
            max-width: 400px; 
            border: 1px solid #e1e3e5; 
            border-radius: 12px; 
            background: white; 
            padding: 40px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .brand-icon {
            color: #008060;
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .btn-primary { 
            background: #008060; 
            border: none; 
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary:hover { background: #006e52; }
        .form-control {
            padding: 12px;
            border-radius: 8px;
        }
        .form-label {
            font-weight: 500;
            color: #4a4a4a;
        }
    </style>
</head>
<body>
    <div class="login-card text-center">
        <div class="brand-icon"><i class="fab fa-shopify"></i></div>
        <h4 class="mb-4 fw-bold text-dark">Admin Panel</h4>
        
        <form method="POST" action="{{ route('msdev2.admin.login.submit') }}" class="text-start">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="far fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control border-start-0" placeholder="name@example.com" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-lock text-muted"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 rounded-3 shadow-sm">Sign In</button>
        </form>
        
        <div class="mt-4 text-muted small">
            &copy; {{ date('Y') }} MsDev2. All rights reserved.
        </div>
    </div>
</body>
</html>