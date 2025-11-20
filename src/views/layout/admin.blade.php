<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Mode - Admin</title>
    
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --shopify-green: #008060;
            --shopify-bg: #f6f6f7;
            --sidebar-width: 240px;
        }
        body { background-color: var(--shopify-bg); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 0; left: 0; height: 100vh;
            background: white;
            border-right: 1px solid #e1e3e5;
            padding-top: 20px;
        }
        .sidebar .nav-link {
            color: #5c5f62;
            font-weight: 500;
            padding: 10px 20px;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #f1f2f3;
            color: var(--shopify-green);
            border-left-color: var(--shopify-green);
        }
        .sidebar .brand {
            font-size: 1.2rem;
            font-weight: bold;
            padding: 0 20px 20px;
            color: var(--shopify-green);
        }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); padding: 30px; }
        
        /* Cards */
        .card { border: 1px solid #e1e3e5; border-radius: 8px; box-shadow: 0 0 0 1px rgba(63, 63, 68, 0.05); border: none; margin-bottom: 20px; }
        .card-header { background: white; border-bottom: 1px solid #e1e3e5; font-weight: 600; padding: 15px 20px; border-radius: 8px 8px 0 0; }
        
        /* Badges */
        .badge-active { background-color: #bbe5b3; color: #414f3e; }
        .badge-inactive { background-color: #ffc9c9; color: #751818; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand"><i class="fab fa-shopify"></i> Super Admin</div>
    <nav class="nav flex-column">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="fas fa-home me-2"></i> Dashboard
        </a>
        <a class="nav-link {{ request()->routeIs('admin.shops*') ? 'active' : '' }}" href="{{ route('admin.shops') }}">
            <i class="fas fa-store me-2"></i> Shops
        </a>
         <a class="nav-link {{ request()->routeIs('admin.tickets*') ? 'active' : '' }}" href="{{ route('admin.tickets') }}">
            <i class="fas fa-ticket-alt me-2"></i> Tickets
        </a>
        <a class="nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}" href="{{ route('admin.logs') }}">
            <i class="fas fa-terminal me-2"></i> System Logs
        </a>
    </nav>
</div>

<div class="main-content">
    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@stack('scripts')
</body>
</html>