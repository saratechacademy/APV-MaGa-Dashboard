<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>APV-MaGa — Sign In</title>
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

.auth-layout{width:100%;max-width:920px;display:flex;align-items:flex-start;gap:64px}

/* ===== LEFT: brand + features ===== */
.auth-info{flex:1 1 50%;max-width:380px}

.brand{display:flex;align-items:center;gap:12px;margin-bottom:40px}
.brand-name{font-size:32px;font-weight:800;letter-spacing:-.6px;color:#16a34a;line-height:1.15}
.brand-sub{font-size:15px;color:var(--muted);margin-top:6px;font-weight:500}

.mobile-brand{display:none}
.mobile-brand .brand-name{font-size:26px}
.mobile-brand .brand-sub{font-size:13px;margin-top:4px}

.eyebrow{
  display:inline-flex;align-items:center;gap:8px;font-size:11px;font-weight:700;
  letter-spacing:1.5px;text-transform:uppercase;color:var(--green);margin-bottom:14px;
}
.eyebrow::before{content:'';display:block;width:18px;height:2px;background:linear-gradient(90deg,#22c55e,#15803d)}

@keyframes pulse-dot{
  0%{box-shadow:0 0 0 0 rgba(34,197,94,.45)}
  70%{box-shadow:0 0 0 6px rgba(34,197,94,0)}
  100%{box-shadow:0 0 0 0 rgba(34,197,94,0)}
}
.live-badge{
  display:inline-flex;align-items:center;gap:5px;font-size:10.5px;font-weight:700;color:var(--green);
  letter-spacing:.5px;text-transform:uppercase;margin-left:auto;flex-shrink:0;
}
.live-badge .ping{width:7px;height:7px;border-radius:50%;background:#22c55e;animation:pulse-dot 2s infinite}

.features{display:flex;flex-direction:column;gap:14px}
.feature{display:flex;align-items:center;gap:14px;font-size:14px;color:#334155;font-weight:500;line-height:1.4;
  background:var(--input-bg);border:1px solid var(--border);border-radius:11px;padding:14px 16px}
.feature.f-purple .dot{background:#f5f3ff}
.feature .dot{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.feature .dot svg{width:17px;height:17px}
.feature.f-green .dot{background:var(--green-bg)}
.feature.f-blue .dot{background:var(--green-bg)}
.feature.f-amber .dot{background:#fffbeb}

/* ===== RIGHT: login card ===== */
.auth-form-side{flex:1 1 50%;max-width:400px;padding-top:6px}

.card{
  background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:32px;
  box-shadow:0 20px 45px -20px rgba(15,23,42,.14), 0 2px 8px rgba(15,23,42,.04);
  position:relative;overflow:hidden;
  isolation:isolate;
}
.card-accent{position:absolute;top:-1px;left:-1px;right:-1px;height:5px;background:linear-gradient(90deg,#22c55e,#15803d)}

.card-title{font-size:19px;font-weight:800;color:var(--text);margin-bottom:4px;letter-spacing:-.3px}
.card-sub{font-size:13px;color:var(--muted);margin-bottom:22px}

.field{margin-bottom:16px}
.field label{display:block;font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.field input{width:100%;padding:11px 14px;background:var(--input-bg);border:1px solid var(--border);border-radius:9px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:14px;transition:border-color .15s,background .15s;outline:none}
.field input::placeholder{color:#94a3b8}
.field input:focus{border-color:var(--green);background:#ffffff}
.field input.error{border-color:var(--red)}

.check-row{display:flex;align-items:center;gap:8px;margin-bottom:20px}
.check-row input[type=checkbox]{accent-color:var(--green);width:15px;height:15px;cursor:pointer}
.check-row label{font-size:13px;color:var(--muted);cursor:pointer}

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

.alert-success{background:var(--green-bg);border:1px solid var(--green-bd);border-radius:8px;padding:10px 14px;margin-bottom:16px;color:var(--green);font-size:13px;display:flex;align-items:center;gap:6px}

.divider{text-align:center;margin:18px 0;position:relative;color:var(--muted);font-size:12px}
.divider::before,.divider::after{content:'';position:absolute;top:50%;width:42%;height:1px;background:var(--border)}
.divider::before{left:0}.divider::after{right:0}

.register-link{text-align:center;margin-top:18px;font-size:13px;color:var(--muted)}
.register-link a{color:var(--green);text-decoration:none;font-weight:600}
.register-link a:hover{color:var(--green-dark)}

.page-footer{text-align:center;margin-top:18px;font-size:11px;color:#a3aebd}
.page-footer span{font-weight:600;color:#94a3b8}

/* ===== Mobile: stack everything ===== */
@media (max-width:900px){
  .mobile-brand{display:block;text-align:center;margin-bottom:24px}
  .auth-layout{flex-direction:column;gap:28px;max-width:420px}
  .auth-info{order:2;width:100%;max-width:none}
  .auth-info > .brand{display:none}
  .auth-form-side{order:1;width:100%;max-width:none}
  .features{margin-top:0}
}
</style>
</head>
<body>
<div class="mobile-brand">
  <div class="brand-name">APV-MaGa</div>
  <div class="brand-sub">Agrivoltaic Monitoring Platform</div>
</div>

<div class="auth-layout">

  <div class="auth-info">
    <div class="brand">
      <div>
        <div class="brand-name">APV-MaGa</div>
        <div class="brand-sub">Agrivoltaic Monitoring Platform</div>
      </div>
    </div>

    <div class="eyebrow">Platform Highlights</div>
    <div class="features">
      <div class="feature f-green">
        <span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/></svg></span>
        <span>Real-time data from solar, water, weather & irrigation sensors</span>
        <span class="live-badge"><span class="ping"></span>Live</span>
      </div>
      <div class="feature f-blue">
        <span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg></span>
        Remote control for valves, pumps & cooling fans
      </div>
      <div class="feature f-amber">
        <span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg></span>
        <span>Multi-site dashboards for research & operations teams</span>
      </div>
      <div class="feature f-purple">
        <span class="dot"><svg viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
        <span>Role-based access — each user sees only their assigned sites</span>
      </div>
    </div>
  </div>

  <div class="auth-form-side">
    <div class="card">
      <div class="card-accent"></div>
      <div class="card-title">Welcome back</div>
      <div class="card-sub">Sign in to access your monitoring dashboard</div>

      @if (session('status'))
        <div class="alert-success">✓ {{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <ul class="errors">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      @endif

      <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
          <label for="email">Email address</label>
          <input id="email" name="email" type="email" value="{{ old('email') }}"
                 placeholder="you@example.com" autocomplete="email" required
                 class="{{ $errors->has('email') ? 'error' : '' }}">
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password"
                 placeholder="Enter your password" autocomplete="current-password" required
                 class="{{ $errors->has('password') ? 'error' : '' }}">
        </div>

        <div class="check-row">
          <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
          <label for="remember">Remember me</label>
          @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" style="margin-left:auto;font-size:12px;color:var(--green);text-decoration:none;font-weight:600">Forgot password?</a>
          @endif
        </div>

        <button type="submit" class="btn-login">Sign in →</button>
      </form>

      @if (Route::has('register'))
        <div class="divider">or</div>
        <div class="register-link">
          Don't have an account? <a href="{{ route('register') }}">Request access</a>
        </div>
      @endif
    </div>

    <div class="page-footer">
      &copy; {{ date('Y') }} APV-MaGa &middot; Developed by <span>Saratech</span> &middot; Funded by <span>UNU</span>
    </div>
  </div>

</div>
</body>
</html>