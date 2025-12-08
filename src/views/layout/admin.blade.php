<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Mode</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --shopify-green: #008060;
            --shopify-bg: #f1f2f4;
            --sidebar-width: 250px;
            --primary-text: #1a1c1d;
            --secondary-text: #6d7175;
        }
        body { 
            background-color: var(--shopify-bg); 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; 
            color: var(--primary-text);
            font-size: 0.95rem;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            top: 0; left: 0; height: 100vh;
            background: white;
            border-right: 1px solid #e1e3e5;
            padding-top: 0;
            z-index: 1040;
            transition: transform 0.3s ease-in-out;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            height: 60px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            border-bottom: 1px solid #f1f2f4;
        }
        .sidebar .brand {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--shopify-green);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar .nav-link {
            color: var(--secondary-text);
            font-weight: 500;
            padding: 12px 20px;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar .nav-link:hover {
            background-color: #f6f6f7;
            color: var(--primary-text);
        }
        .sidebar .nav-link.active {
            background-color: #f0fdf4;
            color: var(--shopify-green);
            border-left-color: var(--shopify-green);
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content { 
            margin-left: var(--sidebar-width); 
            padding: 30px; 
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            height: 60px;
            background: white;
            border-bottom: 1px solid #e1e3e5;
            align-items: center;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 1030;
            justify-content: space-between;
        }
        
        /* Cards */
        .card { 
            border: 1px solid #e1e3e5; 
            border-radius: 12px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.02), 0 1px 0 rgba(0,0,0,0.03); 
            margin-bottom: 24px; 
            background: white;
        }
        .card-header { 
            background: white; 
            border-bottom: 1px solid #f1f2f4; 
            font-weight: 600; 
            padding: 16px 20px; 
            border-radius: 12px 12px 0 0 !important; 
        }
        .card-body {
            padding: 20px;
        }
        
        /* Badges */
        .badge { font-weight: 500; padding: 0.5em 0.8em; }
        
        /* Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1035;
            opacity: 0;
            transition: opacity 0.3s;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .mobile-header {
                display: flex;
            }
            .sidebar-overlay.show {
                display: block;
                opacity: 1;
            }
        }
    </style>
</head>
<body>

<!-- Mobile Header -->
<div class="mobile-header">
    <button class="btn btn-link text-dark p-0" id="sidebarToggle">
        <i class="fas fa-bars fa-lg"></i>
    </button>
    <div class="fw-bold text-dark">Admin Panel</div>
    <div style="width: 24px;"></div> <!-- Spacer for centering -->
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="#" class="brand">
            <i class="fab fa-shopify fa-lg"></i> 
            <span>Super Admin</span>
        </a>
    </div>
    <nav class="nav flex-column mt-2 flex-grow-1">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a class="nav-link {{ request()->routeIs('admin.shops*') ? 'active' : '' }}" href="{{ route('admin.shops') }}">
            <i class="fas fa-store"></i> Shops
        </a>
         <a class="nav-link {{ request()->routeIs('admin.tickets*') ? 'active' : '' }}" href="{{ route('admin.tickets') }}">
            <i class="fas fa-ticket-alt"></i> Tickets
        </a>
        <a class="nav-link {{ request()->routeIs('admin.marketing*') ? 'active' : '' }}" href="{{ route('admin.marketing') }}">
            <i class="fas fa-bullhorn"></i> Marketing
        </a>
        <a class="nav-link {{ request()->routeIs('admin.env*') ? 'active' : '' }}" href="{{ route('admin.env') }}">
            <i class="fas fa-cogs"></i> Environment
        </a>
        <a class="nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}" href="{{ route('admin.logs') }}">
            <i class="fas fa-terminal"></i> System Logs
        </a>
    </nav>
    
    <div class="p-3 border-top">
        <form action="{{ route('msdev2.admin.logout') }}" method="POST">
            @csrf
            <button type="submit" class="nav-link w-100 text-start text-danger border-0 bg-transparent">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </div>
</div>

<div class="main-content">
    @yield('content')
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
    }

    if(sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    if(sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }
</script>
@stack('scripts')
</body>
</html>