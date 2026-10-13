@extends('emails.layout')
@section('subject', 'New account awaiting approval on APV-MaGa')
@section('content')
  <h2 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#0d1321;">New account awaiting approval</h2>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6fb;border-radius:8px;margin-bottom:20px;">
    <tr>
      <td style="padding:14px 16px;">
        <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:#0d1321;">{{ $user->name }}</p>
        <p style="margin:0;font-size:12px;color:#64748b;">
          {{ $user->email }}@if($user->organisation) · {{ $user->organisation }}@endif @if($user->country) · {{ $user->country }}@endif
        </p>
      </td>
    </tr>
  </table>
  <table role="presentation" cellpadding="0" cellspacing="0">
    <tr>
      <td style="border-radius:7px;background-color:#16a34a;background-image:linear-gradient(135deg,#22c55e 0%,#16a34a 45%,#15803d 130%);">
        <a href="{{ $usersUrl }}" style="display:inline-block;padding:11px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:7px;">
          Review accounts
        </a>
      </td>
    </tr>
  </table>
@endsection
