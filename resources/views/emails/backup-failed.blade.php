@extends('emails.layout')
@section('subject', 'APV-MaGa: the daily database backup failed')
@section('content')
  <p style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#334155;">
    Tonight's copy of the APV-MaGa database on <strong>{{ config('app.url') }}</strong> <strong>could not be made</strong>.
  </p>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef3c7;border-radius:8px;margin-bottom:16px;">
    <tr>
      <td style="padding:12px 16px;font-size:13px;color:#0d1321;font-family:monospace;">{{ $reason }}</td>
    </tr>
  </table>
  <p style="margin:0;font-size:14px;line-height:1.6;color:#334155;">
    The dashboard itself keeps working. Run <code>php artisan db:backup</code> on the server to retry once the cause is fixed.
  </p>
@endsection
