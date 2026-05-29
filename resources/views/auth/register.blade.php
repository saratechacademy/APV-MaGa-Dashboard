<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>APV-MaGa — Request Access</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--sidebar-bg:#0f1929;--blue:#1d6ed8;--blue-dark:#1a5fc0;--border:#e4e8ef;--muted:#64748b;}
body{font-family:'DM Sans',sans-serif;background:var(--sidebar-bg);min-height:100vh;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}
body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(30,50,84,.4) 1px,transparent 1px),linear-gradient(90deg,rgba(30,50,84,.4) 1px,transparent 1px);background-size:40px 40px;}
body::after{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 60% 60% at 70% 50%,rgba(29,110,216,.10) 0%,transparent 70%);}
.wrap{position:relative;z-index:10;width:100%;max-width:480px;padding:20px}
.brand{text-align:center;margin-bottom:28px}
.brand-mark{display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:13px;background:linear-gradient(135deg,#1e3254,#1d6ed8);border:1px solid rgba(147,197,253,.2);margin-bottom:12px}
.brand-mark svg{width:26px;height:26px}
.brand-name{font-size:20px;font-weight:600;color:#fff;letter-spacing:-.3px}
.brand-sub{font-size:13px;color:#475569;margin-top:2px}
.card{background:rgba(255,255,255,.04);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:28px}
.card-title{font-size:15px;font-weight:600;color:#f1f5f9;margin-bottom:3px}
.card-sub{font-size:12px;color:#64748b;margin-bottom:22px}
.notice{background:rgba(29,110,216,.1);border:1px solid rgba(29,110,216,.2);border-radius:8px;padding:10px 14px;margin-bottom:18px;color:#93c5fd;font-size:12px;display:flex;align-items:flex-start;gap:8px}
.notice strong{display:block;margin-bottom:2px;font-size:13px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.field{margin-bottom:14px}
.field label{display:block;font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
.field input,.field select{width:100%;padding:10px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:9px;color:#f1f5f9;font-family:'DM Sans',sans-serif;font-size:14px;outline:none;transition:border-color .15s}
.field input::placeholder{color:#334155}
.field input:focus,.field select:focus{border-color:rgba(29,110,216,.6);background:rgba(255,255,255,.09)}
.field select option{background:#0f1929;color:#f1f5f9}
.field input.error{border-color:rgba(190,18,60,.5)}
.errors{background:rgba(190,18,60,.1);border:1px solid rgba(190,18,60,.25);border-radius:8px;padding:10px 14px;margin-bottom:14px}
.errors li{color:#fda4af;font-size:13px;list-style:none;padding:2px 0}
.errors li::before{content:'• '}
.btn-submit{width:100%;padding:11px;background:var(--blue);border:none;border-radius:9px;color:#fff;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;margin-top:6px}
.btn-submit:hover{background:var(--blue-dark)}
.login-link{text-align:center;margin-top:18px;font-size:13px;color:#475569}
.login-link a{color:#93c5fd;text-decoration:none;font-weight:500}
.footer{text-align:center;margin-top:20px;font-size:11px;color:#334155}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand">
    <div class="brand-mark">
      <svg viewBox="0 0 28 28" fill="none"><path d="M14 3L3 10v15h8v-8h6v8h8V10L14 3z" fill="rgba(147,197,253,.15)" stroke="rgba(147,197,253,.6)" stroke-width="1.5" stroke-linejoin="round"/><circle cx="14" cy="9" r="2" fill="#93c5fd" opacity=".8"/></svg>
    </div>
    <div class="brand-name">APV-MaGa</div>
    <div class="brand-sub">Agrivoltaic Monitoring Platform</div>
  </div>

  <div class="card">
    <div class="card-title">Request Access</div>
    <div class="card-sub">Create your account — an admin will activate it shortly</div>

    <div class="notice">
      <span>ℹ</span>
      <div><strong>Pending Approval</strong>Your account will be reviewed by an administrator before you can access the dashboard.</div>
    </div>

    @if ($errors->any())
      <ul class="errors">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    @endif

    <form method="POST" action="{{ route('register') }}">
      @csrf

      <div class="field">
        <label for="name">Full name</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Your full name" required class="{{ $errors->has('name') ? 'error' : '' }}">
      </div>

      <div class="field">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" required class="{{ $errors->has('email') ? 'error' : '' }}">
      </div>

      <div class="row2">
        <div class="field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="Min 8 characters" required class="{{ $errors->has('password') ? 'error' : '' }}">
        </div>
        <div class="field">
          <label for="password_confirmation">Confirm password</label>
          <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repeat password" required>
        </div>
      </div>

      <div class="row2">
        <div class="field">
          <label for="organisation">Organisation</label>
          <input id="organisation" name="organisation" type="text" value="{{ old('organisation') }}" placeholder="e.g. UTG, IER…">
        </div>
        <div class="field">
          <label for="country">Country</label>
          <select id="country" name="country">
            <option value="">Select…</option>
            <option value="Gambia" {{ old('country')=='Gambia' ? 'selected' : '' }}>🇬🇲 Gambia</option>
            <option value="Mali"   {{ old('country')=='Mali'   ? 'selected' : '' }}>🇲🇱 Mali</option>
            <option value="Other"  {{ old('country')=='Other'  ? 'selected' : '' }}>Other</option>
          </select>
        </div>
      </div>

      <div class="field">
        <label for="phone">Phone (optional)</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+220 …">
      </div>

      <button type="submit" class="btn-submit">Request Access →</button>
    </form>

    <div class="login-link">Already have an account? <a href="{{ route('login') }}">Sign in</a></div>
  </div>

  <div class="footer">APV-MaGa v1.0 · Gambia &amp; Mali · {{ date('Y') }}</div>
</div>
</body>
</html>
