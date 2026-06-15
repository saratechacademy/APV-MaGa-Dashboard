@extends('emails.layout')
@section('subject', 'Reset your APV-MaGa password')
@section('content')
  <h2 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#0d1321;">Reset your password</h2>
  <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
    We received a request to reset the password for your <strong>APV-MaGa</strong> account
    ({{ $user->email }}).
  </p>
  <table role="presentation" cellpadding="0" cellspacing="0" style="margin-bottom:20px;">
    <tr>
      <td style="border-radius:7px;background-color:#16a34a;background-image:linear-gradient(135deg,#22c55e 0%,#16a34a 45%,#15803d 130%);">
        <a href="{{ $resetUrl }}" style="display:inline-block;padding:11px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:7px;">
          Reset password
        </a>
      </td>
    </tr>
  </table>
  <p style="margin:0 0 8px;font-size:12px;line-height:1.6;color:#94a3b8;">
    This link will expire in {{ $expireMinutes }} minutes. If you did not request a password reset, no further action is required — your password will remain unchanged.
  </p>
  <p style="margin:16px 0 0;font-size:11px;line-height:1.6;color:#94a3b8;word-break:break-all;">
    If the button above doesn't work, copy and paste this URL into your browser:<br>
    <a href="{{ $resetUrl }}" style="color:#16a34a;">{{ $resetUrl }}</a>
  </p>
@endsection