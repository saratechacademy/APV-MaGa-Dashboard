@extends('emails.layout')
@section('subject', 'New site access on APV-MaGa')
@section('content')
  <h2 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#0d1321;">Hi {{ $user->name }},</h2>
  <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#334155;">
    You've been granted access to a site on <strong>APV-MaGa</strong>:
  </p>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6fb;border-radius:8px;margin-bottom:20px;">
    <tr>
      <td style="padding:14px 16px;">
        <p style="margin:0 0 6px;font-size:12px;color:#64748b;">Site</p>
        <p style="margin:0 0 12px;font-size:14px;font-weight:600;color:#0d1321;">{{ $site->name }}</p>
        <p style="margin:0 0 6px;font-size:12px;color:#64748b;">Your role on this site</p>
        <p style="margin:0;font-size:14px;font-weight:600;color:#0d1321;text-transform:capitalize;">{{ $role }}</p>
      </td>
    </tr>
  </table>
  <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#334155;">
    @if($role === 'agent')
    As an <strong>agent</strong>, you can view live data, enter manual readings, and export reports for this site.
    @else
    As an <strong>observer</strong>, you can view live data and export reports for this site.
    @endif
  </p>
  <table role="presentation" cellpadding="0" cellspacing="0">
    <tr>
      <td style="border-radius:7px;background-color:#16a34a;background-image:linear-gradient(135deg,#22c55e 0%,#16a34a 45%,#15803d 130%);">
        <a href="{{ $loginUrl }}" style="display:inline-block;padding:11px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:7px;">
          Open dashboard
        </a>
      </td>
    </tr>
  </table>
@endsection