<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') }} |  Agent </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    @yield('styles')
</head>
<body class="d-flex flex-column h-100">
    @guest

    @else
    <header>
      <!-- Fixed navbar -->
      <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
        <div class="container-fluid">
          <a class="navbar-brand d-none" href="#">Agent</a>
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
            </ul>
            <form class="d-flex">
              <a class="btn btn-outline-danger" href="{{route('msdev2.agent.logout')}}">Logout</a>
            </form>
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

    <footer class="footer position-fixed bottom-0 w-100 mt-auto py-3 bg-light">
      <div class="container">
        <span class="text-muted">Place sticky footer content here.</span>
      </div>
    </footer>

    @yield('scripts')
</body>
</html>
