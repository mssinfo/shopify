<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') }} |  Agent </title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">  
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script> 

@yield('styles')
    <style>
      /* Polished styles for Agent UI */
      body.layout-agent, body { background: linear-gradient(180deg,#f3f6fb 0%, #ffffff 100%); }

      .navbar-brand.d-none { display: inline-block !important; }

      /* Cards */
      .card.h-100 { border-radius: .8rem; box-shadow: 0 6px 18px rgba(17,24,39,0.06); }
      .card .text-lg { font-size: 1.35rem; }

  /* Suggestions & details */
  #agent-shop-suggestions { max-height: 260px; overflow:auto; position: absolute; width:100%; top:100%; left:0; z-index:1200; }
  .agent-search-wrapper{ position: relative; }
      #agent-shop-detail pre { white-space: pre-wrap; word-break: break-word; }

      /* Responsive tweaks */
      @media (max-width: 576px) { .card .text-lg { font-size: 1rem; } }

      /* Dashboard layout */
      .dashboard-top { gap: 1rem; display: flex; flex-wrap: wrap; }
      .metric-icon { opacity: .65; }
    </style>
</head>
<body class="d-flex flex-column h-100">
    @guest

    @else
    <header>
      <!-- Fixed navbar -->
      <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
        <div class="container-fluid">
          <a class="navbar-brand" href="{{ route('msdev2.agent.dashboard') }}">{{ config('app.name') }} Agent</a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarCollapse">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
              <li class="nav-item">
                <a class="nav-link" href="{{route('msdev2.agent.dashboard')}}">Dashboard</a>
              </li>
              <li class="nav-item">
                <a class="nav-link"  href="{{route('msdev2.agent.tickets')}}">Ticket</a>
              </li>
              <li class="nav-item">
                <a class="nav-link"  href="{{route('msdev2.agent.logs')}}">Log</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="{{route('msdev2.agent.shops')}}">Shops</a>
              </li>
            </ul>
            <form class="d-flex me-3" role="search" onsubmit="event.preventDefault(); var q=this.query.value; if(q) window.location='{{ url('/agent/shops') }}?q='+encodeURIComponent(q);">
              <input class="form-control form-control-sm me-2" type="search" name="query" placeholder="Search shops..." aria-label="Search">
              <button class="btn btn-sm btn-outline-light" type="submit">Search</button>
            </form>
            <div class="d-flex align-items-center">
              <div class="me-2 text-light small">{{ Auth::user()->name ?? '' }}</div>
              <a class="btn btn-outline-danger btn-sm" href="{{route('msdev2.agent.logout')}}">Logout</a>
            </div>
          </div>
        </div>
      </nav>
    </header>
    @endguest
    <!-- Begin page content -->
    <main class="flex-shrink-0">
      <div class="container">
        <br>
        <br>
        <br>
        @yield('content')
      </div>
    </main>

    <footer class="footer position-fixed bottom-0 w-100 mt-auto py-3 bg-light d-none">
      <div class="container">
        <span class="text-muted">Place sticky footer content here.</span>
      </div>
    </footer>

    @yield('scripts')
</body>
</html>
