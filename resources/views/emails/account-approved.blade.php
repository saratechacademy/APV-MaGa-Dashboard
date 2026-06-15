@extends('emails.layout')
@section('subject', 'Your APV-MaGa account is active')
@section('content')
  <h2 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#0d1321;">Hi {{ $user->name }},</h2>
  <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
    Good news — your <strong>APV-MaGa</strong> account has been reviewed and is now
    <span style="display:inline-block;padding:2px 9px;border-radius:20px;background-color:#dcfce7;color:#15803d;font-size:12px;font-weight:600;">active</span>.
  </p>
  <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#334155;">
    You can now log in and access your assigned sites.
  </p>
  <table role="presentation" cellpadding="0" cellspacing="0">
    <tr>
      <td style="border-radius:7px;background-color:#16a34a;background-image:linear-gradient(135deg,#22c55e 0%,#16a34a 45%,#15803d 130%);">
        <a href="{{ $loginUrl }}" style="display:inline-block;padding:11px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:7px;">
          Log in to APV-MaGa
        </a>
      </td>
    </tr>
  </table>
@endsection