<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('subject', 'APV-MaGa')</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6fb;font-family:'Segoe UI',Arial,sans-serif;color:#0d1321;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6fb;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);border:1px solid #e7ebf1;">
          {{-- Accent bar --}}
          <tr>
            <td height="4" style="background-color:#16a34a;background-image:linear-gradient(90deg,#22c55e,#15803d);font-size:0;line-height:0;">&nbsp;</td>
          </tr>
          {{-- Header --}}
          <tr>
            <td style="padding:28px 32px 20px;text-align:center;">
              <div style="font-size:24px;font-weight:800;color:#16a34a;letter-spacing:-0.5px;font-family:'Segoe UI',Arial,sans-serif;">APV-MaGa</div>
              <div style="font-size:12px;color:#64748b;margin-top:4px;">Agrivoltaic Monitoring Platform</div>
            </td>
          </tr>
          {{-- Content --}}
          <tr>
            <td style="padding:8px 32px 32px;">
              @yield('content')
            </td>
          </tr>
          {{-- Footer --}}
          <tr>
            <td style="padding:20px 32px;border-top:1px solid #e7ebf1;">
              <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.6;text-align:center;">
                &copy; {{ date('Y') }} APV-MaGa Dashboard &middot; {{ __('Developed by') }} <strong style="color:#64748b;">Saratech</strong> &middot; {{ __('Funded by') }} <strong style="color:#64748b;">UNU</strong>
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>