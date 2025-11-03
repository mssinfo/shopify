@extends('msdev2::layout.agent')
@section('content')
<div class="d-flex align-items-center justify-content-center vh-90 w-100">
  <main class="form-signin card shadow-lg p-4" style="width: 420px;">
    @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif
    <form method="post" action="{{route('msdev2.agent.dologin')}}">
        @csrf
      <div class="text-center mb-3">
        <img class="mb-2" src="{{ config("msdev2.menu.logo.value") }}" alt="logo" width="88" height="70">
        <h1 class="h4 mb-0 fw-bold text-primary">{{ config('app.name') }}</h1>
        <div class="small text-muted">Agent Portal</div>
      </div>

      <h2 class="h5 mb-3 fw-normal text-center">Sign in to your agent account</h2>

      <div class="form-floating">
        <input type="email" name="email" class="form-control" id="floatingInput" placeholder="name@example.com">
        <label for="floatingInput">Email address</label>
      </div>
      @if (isset($errors) && $errors->has('email'))
            <span class="invalid-feedback">
                <strong>{{ $errors->first('email') }}</strong>
            </span>
        @endif
      <div class="form-floating">
        <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Password">
        <label for="floatingPassword">Password</label>
      </div>
      @if (isset($errors) && $errors->has('password'))
            <span class="invalid-feedback">
                <strong>{{ $errors->first('password') }}</strong>
            </span>
        @endif
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="remember-me" id="rememberMe">
          <label class="form-check-label small text-muted" for="rememberMe">Remember me</label>
        </div>
        <a href="#" class="small">Forgot?</a>
      </div>
      <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
    </form>
    </main>
</div>
@endsection
@section("styles")
    <style>
        body {
  background-color: #f5f5f5;margin:0; 
}
.bd-placeholder-img {
    font-size: 1.125rem;
    text-anchor: middle;
    -webkit-user-select: none;
    -moz-user-select: none;
}
.form-signin {
  border-radius: 12px;
}
.form-signin h2 { color: #343a40; }
.form-signin .form-control { height: calc(2.25rem + 10px); }

/* small responsive tweaks */
@media (max-width: 576px) {
  .form-signin { width: 92% !important; }
}
  </style>
 
