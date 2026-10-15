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
          Silent for {{ $event['since']->diffForHumans($event['back'], true) }},
          since {{ $event['since']->format('d M Y, H:i') }} UTC
        </p>
      </td>
    </tr>
    @endforeach
  </table>
  @endif

  @if($sensorsDown)
  <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:#334155;">
    {{ $down ? 'Also, these sensors' : 'These sensors' }} <strong>stopped reporting</strong> while the rest of the site still does:
  </p>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fef3c7;border-radius:8px;margin-bottom:20px;">
    @foreach(\App\Mail\SiteStatusMail::byDevice($sensorsDown) as $event)
    <tr>
      <td style="padding:12px 16px;{{ $loop->last ? '' : 'border-bottom:1px solid #fde68a;' }}">
        <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:#0d1321;">{{ $event['device'] }}</p>
        <p style="margin:0;font-size:12px;color:#64748b;">
          @if($event['measurements']){{ implode(', ', $event['measurements']) }} · @endif
          Last reading: {{ $event['since']->format('d M Y, H:i') }} UTC ({{ $event['since']->diffForHumans() }})
        </p>
      </td>
    </tr>
    @endforeach
  </table>
  @endif

  @if($sensorsUp)
  <p style="margin:0 0 10px;font-size:14px;line-height:1.6;color:#334155;">
    These sensors are <strong>reporting again</strong>:
  </p>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#dcfce7;border-radius:8px;margin-bottom:20px;">
    @foreach(\App\Mail\SiteStatusMail::byDevice($sensorsUp) as $event)
    <tr>
      <td style="padding:12px 16px;{{ $loop->last ? '' : 'border-bottom:1px solid #bbf7d0;' }}">
        <p style="margin:0 0 4px;font-size:14px;font-weight:600;color:#0d1321;">{{ $event['device'] }}</p>
        <p style="margin:0;font-size:12px;color:#64748b;">
          Silent for {{ $event['since']->diffForHumans($event['back'], true) }},
          since {{ $event['since']->format('d M Y, H:i') }} UTC
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
@endsection
