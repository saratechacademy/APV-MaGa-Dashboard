<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>APV-MaGa — Request Access</title>
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
html{overflow-x:hidden}
body{
  font-family:'DM Sans',sans-serif;color:var(--text);background:#ffffff;
  min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;padding:40px 20px;
}

.auth-layout{width:100%;max-width:980px;display:flex;align-items:flex-start;gap:56px}

/* ===== LEFT: brand + notice ===== */
.auth-info{flex:1 1 38%;max-width:320px;padding-top:6px}

.brand-name{font-size:30px;font-weight:800;letter-spacing:-.6px;color:#16a34a;line-height:1.15}
.brand-sub{font-size:14px;color:var(--muted);margin-top:6px;margin-bottom:28px;font-weight:500}

.mobile-brand{display:none}
.mobile-brand .brand-name{font-size:26px}
.mobile-brand .brand-sub{font-size:13px;margin-bottom:0}

.notice{
  background:var(--green-bg);border:1px solid var(--green-bd);border-radius:12px;
  padding:16px 18px;color:var(--green);font-size:13px;
  display:flex;align-items:flex-start;gap:10px;line-height:1.55;text-align:left;
}
.notice .ico{flex-shrink:0;margin-top:1px}
.notice .ico svg{width:18px;height:18px}
.notice strong{display:block;margin-bottom:3px;font-size:14px;color:var(--text)}

/* ===== RIGHT: form card ===== */
.auth-form-side{flex:1 1 62%;max-width:460px}

.card{
  background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:28px 32px;
  box-shadow:0 20px 45px -20px rgba(15,23,42,.14), 0 2px 8px rgba(15,23,42,.04);
  position:relative;overflow:hidden;isolation:isolate;
}
.card-accent{position:absolute;top:-1px;left:-1px;right:-1px;height:5px;background:linear-gradient(90deg,#22c55e,#15803d)}

.card-title{font-size:19px;font-weight:800;color:var(--text);margin-bottom:4px;letter-spacing:-.3px}
.card-sub{font-size:13px;color:var(--muted);margin-bottom:20px;line-height:1.6}

.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}

.field{margin-bottom:14px}
.field label{display:block;font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
.field input,.field select{width:100%;padding:10px 13px;background:var(--input-bg);border:1px solid var(--border);border-radius:9px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:14px;transition:border-color .15s,background .15s;outline:none}
.field input::placeholder{color:#94a3b8}
.field input:focus,.field select:focus{border-color:var(--green);background:#ffffff}
.field input.error{border-color:var(--red)}
.field select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;background-size:16px;padding-right:36px}

.errors{background:var(--red-bg);border:1px solid var(--red-bd);border-radius:8px;padding:10px 14px;margin-bottom:16px}
.errors li{color:var(--red);font-size:13px;list-style:none;display:flex;align-items:center;gap:6px}
.errors li::before{content:'•';color:var(--red)}

.btn-submit{
  width:100%;padding:12px;border:none;border-radius:9px;color:#fff;
  font-family:'DM Sans',sans-serif;font-size:14.5px;font-weight:700;cursor:pointer;
  letter-spacing:.1px;background:linear-gradient(135deg,#22c55e 0%,var(--green) 45%,#15803d 130%);
  background-size:160% 160%;background-position:0% 50%;
  transition:background-position .25s ease,transform .1s;margin-top:4px;
}
.btn-submit:hover{background-position:100% 50%}
.btn-submit:active{transform:scale(.99)}

.login-link{text-align:center;margin-top:18px;font-size:13px;color:var(--muted)}
.login-link a{color:var(--green);text-decoration:none;font-weight:600}
.login-link a:hover{color:var(--green-dark)}

.page-footer{text-align:center;margin-top:20px;font-size:11px;color:#a3aebd}
.page-footer span{font-weight:600;color:#94a3b8}

/* ===== Mobile ===== */
@media (max-width:900px){
  .mobile-brand{display:block;text-align:center;margin-bottom:24px}
  .auth-layout{flex-direction:column;gap:24px;max-width:460px}
  .auth-info{order:2;width:100%;max-width:none;padding-top:0}
  .auth-info > .brand-name,.auth-info > .brand-sub{display:none}
  .auth-form-side{order:1;width:100%;max-width:none}
}

@media (max-width:480px){
  body{padding:12px 14px}
  .card{padding:20px 18px;border-radius:14px}
  .row2{grid-template-columns:1fr;gap:0}
  .field{margin-bottom:10px}
  .field input,.field select{font-size:14px;padding:10px 12px}
  .notice{font-size:12px;padding:12px 14px}
  .page-footer{font-size:10px;line-height:1.6;padding:0 4px}
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
    <div class="brand-name">APV-MaGa</div>
    <div class="brand-sub">Agrivoltaic Monitoring Platform</div>

    <div class="notice">
      <span class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
      </span>
      <div>
        <strong>Pending Approval</strong>
        Your account will be reviewed by an administrator before you can access the dashboard.
      </div>
    </div>
  </div>

  <div class="auth-form-side">
    <div class="card">
      <div class="card-accent"></div>

      <div class="card-title">Request Access</div>
      <div class="card-sub">Create your account — an admin will activate it shortly</div>

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
              <option value="Niger"        {{ old('country')=='Niger'        ? 'selected' : '' }}>🇳🇪 Niger</option>
              <option value="Nigeria"      {{ old('country')=='Nigeria'      ? 'selected' : '' }}>🇳🇬 Nigeria</option>
              <option value="Mali"         {{ old('country')=='Mali'         ? 'selected' : '' }}>🇲🇱 Mali</option>
              <option value="Burkina Faso" {{ old('country')=='Burkina Faso' ? 'selected' : '' }}>🇧🇫 Burkina Faso</option>
              <option value="Senegal"      {{ old('country')=='Senegal'      ? 'selected' : '' }}>🇸🇳 Senegal</option>
              <option value="Benin"        {{ old('country')=='Benin'        ? 'selected' : '' }}>🇧🇯 Benin</option>
              <option value="Togo"         {{ old('country')=='Togo'         ? 'selected' : '' }}>🇹🇬 Togo</option>
              <option value="Ghana"        {{ old('country')=='Ghana'        ? 'selected' : '' }}>🇬🇭 Ghana</option>
              <option value="Cote d'Ivoire" {{ old('country')=="Cote d'Ivoire" ? 'selected' : '' }}>🇨🇮 Côte d'Ivoire</option>
              <option value="Guinea"       {{ old('country')=='Guinea'       ? 'selected' : '' }}>🇬🇳 Guinea</option>
              <option value="Gambia"       {{ old('country')=='Gambia'       ? 'selected' : '' }}>🇬🇲 Gambia</option>
              <option value="Cameroon"     {{ old('country')=='Cameroon'     ? 'selected' : '' }}>🇨🇲 Cameroon</option>
              <option value="Chad"         {{ old('country')=='Chad'         ? 'selected' : '' }}>🇹🇩 Chad</option>
              <option value="Algeria"      {{ old('country')=='Algeria'      ? 'selected' : '' }}>🇩🇿 Algeria</option>
              <option value="Morocco"      {{ old('country')=='Morocco'      ? 'selected' : '' }}>🇲🇦 Morocco</option>
              <option value="Tunisia"      {{ old('country')=='Tunisia'      ? 'selected' : '' }}>🇹🇳 Tunisia</option>
              <option value="Egypt"        {{ old('country')=='Egypt'        ? 'selected' : '' }}>🇪🇬 Egypt</option>
              <option value="Kenya"        {{ old('country')=='Kenya'        ? 'selected' : '' }}>🇰🇪 Kenya</option>
              <option value="Ethiopia"     {{ old('country')=='Ethiopia'     ? 'selected' : '' }}>🇪🇹 Ethiopia</option>
              <option value="Tanzania"     {{ old('country')=='Tanzania'     ? 'selected' : '' }}>🇹🇿 Tanzania</option>
              <option value="South Africa" {{ old('country')=='South Africa' ? 'selected' : '' }}>🇿🇦 South Africa</option>
              <option value="Other"        {{ old('country')=='Other'        ? 'selected' : '' }}>Other</option>
            </select>
          </div>
        </div>

        <div class="field">
          <label for="phone">Phone (optional)</label>
          <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="+227 …">
        </div>

        <button type="submit" class="btn-submit">Request Access →</button>
      </form>

      <div class="login-link">Already have an account? <a href="{{ route('login') }}">Sign in</a></div>
    </div>
  </div>

</div>

<div class="page-footer">
  &copy; {{ date('Y') }} APV-MaGa Dashboard &middot; Developed by <span>Saratech</span> &middot; Funded by <span>UNU</span> &middot; All rights reserved.
</div>
</body>
</html>