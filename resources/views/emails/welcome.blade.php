@extends('emails.layout')
@section('subject', 'Welcome to APV-MaGa')
@section('content')
  <h2 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#0d1321;">Welcome, {{ $user->name }}!</h2>
  <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
    An account has been created for you on the <strong>APV-MaGa</strong> agrivoltaic monitoring platform.
  </p>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6fb;border-radius:8px;margin-bottom:16px;">
    <tr>
      <td style="padding:14px 16px;">
        <p style="margin:0 0 6px;font-size:12px;color:#64748b;">Email</p>
        <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#0d1321;">{{ $user->email }}</p>
        <p style="margin:0 0 6px;font-size:12px;color:#64748b;">Role</p>
        <p style="margin:0;font-size:14px;font-weight:600;color:#0d1321;text-transform:capitalize;">{{ $user->role }}</p>
      </td>
    </tr>
  </table>
  <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#334155;">
    Your password was set by an administrator. If you don't know it yet, you can request a reset link from the login page.
  </p>
  <table role="presentation" cellpadding="0" cellspacing="0">
    <tr>
      <td style="border-radius:7px;background-color:#16a34a;background-image:linear-gradient(135deg,#22c55e 0%,#16a34a 45%,#15803d 130%);">
        <a href="{{ $loginUrl }}" style="display:inline-block;padding:11px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:7px;">
          Go to APV-MaGa
        </a>
      </td>
    </tr>
  </table>
@endsection