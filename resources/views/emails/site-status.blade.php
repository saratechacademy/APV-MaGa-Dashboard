@extends('emails.layout')
@section('subject', 'Site status on APV-MaGa')
@section('content')
  <h2 style="margin:0 0 12px;font-size:18px;font-weight:700;color:#0d1321;">{{ $site->name }}</h2>

  @if($down)
  <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:#334155;">
    The dashboard is <strong>no longer receiving data</strong> from:
  </p>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef3c7;border-radius:8px;margin-bottom:20px;">
    @foreach($down as $event)
    <tr>
      <td style="padding:12px 16px;{{ $loop->last ? '' : 'border-bottom:1px solid #fde68a;' }}">
        <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:#0d1321;">{{ $event['category']->name }}</p>
        <p style="margin:0;font-size:12px;color:#64748b;">
          Last reading: {{ $event['since']->format('d M Y, H:i') }} UTC ({{ $event['since']->diffForHumans() }})
        </p>
      </td>
    </tr>
    @endforeach
  </table>
  <p style="margin:0 0 20px;font-size:13px;line-height:1.6;color:#64748b;">
    This usually means a power cut, a gateway that lost its connection, or a sensor that needs attention on site.
    You will get another message when the data comes back.
  </p>
  @endif

  @if($up)
  <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:#334155;">
    Data is <strong>arriving again</strong> from:
  </p>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#dcfce7;border-radius:8px;margin-bottom:20px;">
    @foreach($up as $event)
    <tr>
      <td style="padding:12px 16px;{{ $loop->last ? '' : 'border-bottom:1px solid #bbf7d0;' }}">
        <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:#0d1321;">{{ $event['category']->name }}</p>
        <p style="margin:0;font-size:12px;color:#64748b;">
          No data from {{ $event['since']->format('d M Y, H:i') }} UTC
          — silent for {{ $event['since']->diffForHumans($event['back'], true) }} at most.
          Latest reading: {{ $event['back']->format('d M Y, H:i') }} UTC.
        </p>
      </td>
    </tr>
    @endforeach
  </table>
  @endif

  <table role="presentation" cellpadding="0" cellspacing="0">
    <tr>
      <td style="border-radius:7px;background-color:#16a34a;background-image:linear-gradient(135deg,#22c55e 0%,#16a34a 45%,#15803d 130%);">
        <a href="{{ $siteUrl }}" style="display:inline-block;padding:11px 24px;font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:7px;">
          Open {{ $site->name }}
        </a>
      </td>
    </tr>
  </table>

  <p style="margin:20px 0 0;font-size:11px;line-height:1.6;color:#94a3b8;">
    You receive this because you are an administrator of APV-MaGa or assigned to this site.
  </p>
@endsection
