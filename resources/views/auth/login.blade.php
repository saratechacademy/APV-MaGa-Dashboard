<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🌿</text></svg>">
<title>APV-MaGa — Sign In</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --sidebar-bg:#0f1929;--sidebar-active:#1e3254;
  --blue:#1d6ed8;--blue-dark:#1a5fc0;
  --green:#15803d;--green-bg:#f0fdf4;--green-bd:#bbf7d0;
  --red:#be123c;--red-bg:#fff1f2;--red-bd:#fecdd3;
  --border:#e4e8ef;--muted:#64748b;--text:#0d1321;
}
body{font-family:'DM Sans',sans-serif;background:var(--sidebar-bg);min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}

/* Background grid */
body::before{
  content:'';position:fixed;inset:0;
  background-image:linear-gradient(rgba(30,50,84,.4) 1px,transparent 1px),linear-gradient(90deg,rgba(30,50,84,.4) 1px,transparent 1px);
  background-size:40px 40px;
}
body::after{
  content:'';position:fixed;inset:0;
  background:radial-gradient(ellipse 60% 60% at 30% 50%,rgba(29,110,216,.12) 0%,transparent 70%),
              radial-gradient(ellipse 40% 40% at 70% 30%,rgba(21,128,61,.08) 0%,transparent 60%);
}

.login-wrap{position:relative;z-index:10;width:100%;max-width:420px;padding:20px}

/* Brand */
.brand{text-align:center;margin-bottom:32px}
.brand-mark{display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#1e3254,#1d6ed8);border:1px solid rgba(147,197,253,.2);margin-bottom:14px}
.brand-mark svg{width:36px;height:36px}
.brand-name{font-size:22px;font-weight:600;color:#fff;letter-spacing:-.3px}
.brand-sub{font-size:13px;color:#475569;margin-top:3px}

/* Card */
.card{background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:32px}

.card-title{font-size:16px;font-weight:600;color:#f1f5f9;margin-bottom:4px}
.card-sub{font-size:13px;color:#64748b;margin-bottom:24px}

/* Form */
.field{margin-bottom:16px}
.field label{display:block;font-size:12px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px}
.field input{width:100%;padding:10px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:9px;color:#f1f5f9;font-family:'DM Sans',sans-serif;font-size:14px;transition:border-color .15s,background .15s;outline:none}
.field input::placeholder{color:#334155}
.field input:focus{border-color:rgba(29,110,216,.6);background:rgba(255,255,255,.09)}
.field input.error{border-color:rgba(190,18,60,.5)}

.check-row{display:flex;align-items:center;gap:8px;margin-bottom:20px}
.check-row input[type=checkbox]{accent-color:var(--blue);width:15px;height:15px;cursor:pointer}
.check-row label{font-size:13px;color:#94a3b8;cursor:pointer}

.btn-login{width:100%;padding:11px;background:var(--blue);border:none;border-radius:9px;color:#fff;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s,transform .1s;letter-spacing:.1px}
.btn-login:hover{background:var(--blue-dark)}
.btn-login:active{transform:scale(.99)}

/* Errors */
.errors{background:rgba(190,18,60,.1);border:1px solid rgba(190,18,60,.25);border-radius:8px;padding:10px 14px;margin-bottom:16px}
.errors li{color:#fda4af;font-size:13px;list-style:none;display:flex;align-items:center;gap:6px}
.errors li::before{content:'•';color:#f43f5e}

/* Success */
.alert-success{background:rgba(21,128,61,.1);border:1px solid rgba(21,128,61,.25);border-radius:8px;padding:10px 14px;margin-bottom:16px;color:#86efac;font-size:13px;display:flex;align-items:center;gap:6px}

/* Divider */
.divider{text-align:center;margin:20px 0;position:relative;color:#334155;font-size:12px}
.divider::before,.divider::after{content:'';position:absolute;top:50%;width:42%;height:1px;background:rgba(255,255,255,.06)}
.divider::before{left:0}.divider::after{right:0}

/* Register link */
.register-link{text-align:center;margin-top:20px;font-size:13px;color:#475569}
.register-link a{color:#93c5fd;text-decoration:none;font-weight:500}
.register-link a:hover{color:#bfdbfe}

/* Footer */
.footer{text-align:center;margin-top:24px;font-size:11px;color:#334155}
.footer span{font-family:'DM Mono',monospace}
</style>
</head>
<body>
<div class="login-wrap">

  <!-- Brand -->
  <div class="brand">
    <div class="brand-mark">
      <!-- Agrivoltaic farm SVG: solar panels + plants + sun -->
      <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Sun -->
        <circle cx="28" cy="8" r="3" fill="#fbbf24" opacity=".9"/>
        <line x1="28" y1="3" x2="28" y2="1.5" stroke="#fbbf24" stroke-width="1.2" stroke-linecap="round" opacity=".7"/>
        <line x1="28" y1="13" x2="28" y2="14.5" stroke="#fbbf24" stroke-width="1.2" stroke-linecap="round" opacity=".7"/>
        <line x1="23" y1="8" x2="21.5" y2="8" stroke="#fbbf24" stroke-width="1.2" stroke-linecap="round" opacity=".7"/>
        <line x1="33" y1="8" x2="34.5" y2="8" stroke="#fbbf24" stroke-width="1.2" stroke-linecap="round" opacity=".7"/>
        <line x1="24.5" y1="4.5" x2="23.4" y2="3.4" stroke="#fbbf24" stroke-width="1.2" stroke-linecap="round" opacity=".7"/>
        <line x1="31.5" y1="11.5" x2="32.6" y2="12.6" stroke="#fbbf24" stroke-width="1.2" stroke-linecap="round" opacity=".7"/>

        <!-- Solar panel 1 (tilted) -->
        <rect x="2" y="10" width="12" height="7" rx="1" transform="rotate(-12 2 10)"
              fill="rgba(29,110,216,.7)" stroke="rgba(147,197,253,.5)" stroke-width=".8"/>
        <line x1="3" y1="12.5" x2="13" y2="10.5" stroke="rgba(147,197,253,.3)" stroke-width=".5"/>
        <line x1="3" y1="15" x2="13" y2="13" stroke="rgba(147,197,253,.3)" stroke-width=".5"/>
        <line x1="8" y1="10.5" x2="8" y2="16.5" stroke="rgba(147,197,253,.3)" stroke-width=".5"/>

        <!-- Solar panel 2 (tilted) -->
        <rect x="14" y="8" width="12" height="7" rx="1" transform="rotate(-12 14 8)"
              fill="rgba(29,110,216,.7)" stroke="rgba(147,197,253,.5)" stroke-width=".8"/>
        <line x1="15" y1="10.5" x2="25" y2="8.5" stroke="rgba(147,197,253,.3)" stroke-width=".5"/>
        <line x1="15" y1="13" x2="25" y2="11" stroke="rgba(147,197,253,.3)" stroke-width=".5"/>
        <line x1="20" y1="8.5" x2="20" y2="14.5" stroke="rgba(147,197,253,.3)" stroke-width=".5"/>

        <!-- Ground line -->
        <line x1="1" y1="28" x2="35" y2="28" stroke="rgba(147,197,253,.2)" stroke-width=".8"/>

        <!-- Panel legs -->
        <line x1="8" y1="17" x2="8" y2="28" stroke="rgba(147,197,253,.3)" stroke-width=".8"/>
        <line x1="20" y1="15" x2="20" y2="28" stroke="rgba(147,197,253,.3)" stroke-width=".8"/>

        <!-- Plants under panels -->
        <!-- Plant 1 -->
        <line x1="5" y1="28" x2="5" y2="22" stroke="#4ade80" stroke-width="1.2" stroke-linecap="round"/>
        <ellipse cx="3.5" cy="21" rx="2" ry="1.2" fill="#4ade80" opacity=".8" transform="rotate(-20 3.5 21)"/>
        <ellipse cx="6.5" cy="20" rx="2" ry="1.2" fill="#4ade80" opacity=".8" transform="rotate(20 6.5 20)"/>

        <!-- Plant 2 -->
        <line x1="13" y1="28" x2="13" y2="23" stroke="#4ade80" stroke-width="1.2" stroke-linecap="round"/>
        <ellipse cx="11" cy="22" rx="2.2" ry="1.3" fill="#4ade80" opacity=".8" transform="rotate(-15 11 22)"/>
        <ellipse cx="15" cy="21.5" rx="2" ry="1.2" fill="#4ade80" opacity=".8" transform="rotate(15 15 21.5)"/>

        <!-- Plant 3 -->
        <line x1="25" y1="28" x2="25" y2="22" stroke="#4ade80" stroke-width="1.2" stroke-linecap="round"/>
        <ellipse cx="23" cy="21" rx="2" ry="1.2" fill="#4ade80" opacity=".8" transform="rotate(-20 23 21)"/>
        <ellipse cx="27" cy="20.5" rx="2.2" ry="1.3" fill="#4ade80" opacity=".8" transform="rotate(20 27 20.5)"/>

        <!-- WiFi/signal waves (IoT) -->
        <path d="M30 22 Q33 19 36 22" stroke="rgba(147,197,253,.5)" stroke-width=".8" fill="none" stroke-linecap="round"/>
        <path d="M31 24 Q33 22 35 24" stroke="rgba(147,197,253,.4)" stroke-width=".8" fill="none" stroke-linecap="round"/>
        <circle cx="33" cy="26" r=".8" fill="rgba(147,197,253,.6)"/>
      </svg>
    </div>
    <div class="brand-name">APV-MaGa</div>
    <div class="brand-sub">Agrivoltaic Monitoring Platform</div>
  </div>

  <!-- Card -->
  <div class="card">
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
               placeholder="••••••••" autocomplete="current-password" required
               class="{{ $errors->has('password') ? 'error' : '' }}">
      </div>

      <div class="check-row">
        <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
        <label for="remember">Remember me</label>
        @if (Route::has('password.request'))
          <a href="{{ route('password.request') }}" style="margin-left:auto;font-size:12px;color:#93c5fd;text-decoration:none">Forgot password?</a>
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

  <div class="footer">
   © {{ date('Y') }} APV-MaGa Dashboard · Developed by <span style="font-weight:600">Saratech</span> · Funded by <span style="font-weight:600">UNU</span> · All rights reserved.
  </div>
</div>
</body>
</html>