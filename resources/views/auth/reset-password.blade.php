<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>APV-MaGa — Reset Password</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#ffffff;--surface:#ffffff;--input-bg:#f4f6fb;
  --green:#16a34a;--green-dark:#15803d;
  --red:#be123c;--red-bg:#fff1f2;--red-bd:#fecdd3;
  --green-bg:#f0fdf4;--green-bd:#bbf7d0;
  --border:#e7ebf1;--muted:#64748b;--text:#0d1321;
}
html,body{height:100%}
body{
  font-family:'DM Sans',sans-serif;color:var(--text);background:#ffffff;
  min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:40px 20px;
}

.login-wrap{width:100%;max-width:420px}

.brand{text-align:center;margin-bottom:32px}
.brand-name{font-size:32px;font-weight:800;letter-spacing:-.6px;color:#16a34a;line-height:1.15}
.brand-sub{font-size:15px;color:var(--muted);margin-top:6px;font-weight:500}

.card{
  background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:32px;
  box-shadow:0 20px 45px -20px rgba(15,23,42,.14), 0 2px 8px rgba(15,23,42,.04);
  position:relative;overflow:hidden;isolation:isolate;
}
.card-accent{position:absolute;top:-1px;left:-1px;right:-1px;height:5px;background:linear-gradient(90deg,#22c55e,#15803d)}

.icon-badge{
  width:44px;height:44px;border-radius:12px;background:var(--green-bg);border:1px solid var(--green-bd);
  display:flex;align-items:center;justify-content:center;margin-bottom:16px;
}
.icon-badge svg{width:22px;height:22px}

.card-title{font-size:19px;font-weight:800;color:var(--text);margin-bottom:4px;letter-spacing:-.3px}
.card-sub{font-size:13px;color:var(--muted);margin-bottom:22px;line-height:1.6}

.field{margin-bottom:16px}
.field label{display:block;font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.field input{width:100%;padding:11px 14px;background:var(--input-bg);border:1px solid var(--border);border-radius:9px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:14px;transition:border-color .15s,background .15s;outline:none}
.field input::placeholder{color:#94a3b8}
.field input:focus{border-color:var(--green);background:#ffffff}
.field input.error{border-color:var(--red)}
.field input[readonly]{opacity:.65;cursor:not-allowed}

.btn-login{
  width:100%;padding:12px;border:none;border-radius:9px;color:#fff;
  font-family:'DM Sans',sans-serif;font-size:14.5px;font-weight:700;cursor:pointer;
  letter-spacing:.1px;background:linear-gradient(135deg,#22c55e 0%,var(--green) 45%,#15803d 130%);
  background-size:160% 160%;background-position:0% 50%;
  transition:background-position .25s ease,transform .1s;
}
.btn-login:hover{background-position:100% 50%}
.btn-login:active{transform:scale(.99)}

.errors{background:var(--red-bg);border:1px solid var(--red-bd);border-radius:8px;padding:10px 14px;margin-bottom:16px}
.errors li{color:var(--red);font-size:13px;list-style:none;display:flex;align-items:center;gap:6px}
.errors li::before{content:'•';color:var(--red)}

.footer{text-align:center;margin-top:24px;font-size:11px;color:#a3aebd}
.footer span{font-weight:600;color:#94a3b8}
</style>
</head>
<body>
<div class="login-wrap">

  <!-- Brand -->
  <div class="brand">
    <div class="brand-name">APV-MaGa</div>
    <div class="brand-sub">Agrivoltaic Monitoring Platform</div>
  </div>

  <!-- Card -->
  <div class="card">
    <div class="card-accent"></div>

    <div class="icon-badge">
      <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="11" width="18" height="11" rx="2"/>
        <circle cx="12" cy="16" r="1"/>
        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
      </svg>
    </div>

    <div class="card-title">Set a new password</div>
    <div class="card-sub">Choose a strong password for your APV-MaGa account.</div>

    @if ($errors->any())
      <ul class="errors">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
      @csrf

      <input type="hidden" name="token" value="{{ $request->route('token') }}">

      <div class="field">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}"
               readonly required
               class="{{ $errors->has('email') ? 'error' : '' }}">
      </div>

      <div class="field">
        <label for="password">New password</label>
        <input id="password" name="password" type="password"
               placeholder="Enter your new password" autocomplete="new-password" required autofocus
               class="{{ $errors->has('password') ? 'error' : '' }}">
      </div>

      <div class="field">
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password"
               placeholder="Re-enter your new password" autocomplete="new-password" required
               class="{{ $errors->has('password_confirmation') ? 'error' : '' }}">
      </div>

      <button type="submit" class="btn-login">Reset password →</button>
    </form>
  </div>

  <div class="footer">
   &copy; {{ date('Y') }} APV-MaGa Dashboard &middot; Developed by <span>Saratech</span> &middot; Funded by <span>UNU</span> &middot; All rights reserved.
  </div>
</div>
</body>
</html>