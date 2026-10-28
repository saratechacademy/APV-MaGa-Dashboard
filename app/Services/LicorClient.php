<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Read-only client for the LI-COR Cloud API (HOBOnet stations).
 */
class LicorClient
{
    public function __construct(
        private readonly ?string $url,
        private readonly ?string $token,
        private readonly int $maxWindowBytes = 4 * 1024 * 1024,
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('licor.url'), config('licor.token'), (int) config('licor.max_window_bytes', 4 * 1024 * 1024));
    }

    public function isConfigured(): bool
    {
        return filled($this->url) && filled($this->token);
    }

    /** Every station of the account, each with its 'sensors'. */
    public function devices(): array
    {
        $body = $this->request()->get('/devices', ['includeSensors' => 'true'])->throw()->json();

        return $body['devices'] ?? $body ?? [];
    }

    /**
     * Raw [epoch ms, value] records of every sensor of a station between two
     * instants, as [sensor serial => records oldest first], following the
     * API's paging. One request covers all sensors: far faster than one per
     * sensor. Keep the range to a few hours: a station logs ~43k records a day.
     *
     * @throws LicorWindowTooLarge when the window holds more than maxWindowBytes
     */
    public function records(string $deviceSerial, int $startMs, int $endMs): array
    {
        $records = [];
        $bytes   = 0;
        $window  = [$startMs, $endMs];

        while ($startMs < $endMs) {
            // Read as a stream, counting: a station that uploads weeks of
            // buffered data at once can answer with more than PHP may hold.
            $response = $this->request()->withOptions(['stream' => true])->get('/data', [
                'deviceSerialNumber' => $deviceSerial,
                'startTime'          => $startMs,
                'endTime'            => $endMs,
            ])->throw();

            $stream = $response->toPsrResponse()->getBody();
            if ($stream->isSeekable()) {
                $stream->rewind();   // a body already read once (test fakes reuse theirs)
            }
            $json   = '';
            while (!$stream->eof()) {
                $json .= $stream->read(1024 * 1024);
                if ($bytes + strlen($json) > $this->maxWindowBytes) {
                    $stream->close();
                    throw new LicorWindowTooLarge(sprintf('station %s: more than %s bytes of data between %s and %s',
                        $deviceSerial, number_format($this->maxWindowBytes), date('Y-m-d H:i', intdiv($window[0], 1000)), date('Y-m-d H:i', intdiv($window[1], 1000))));
                }
            }
            $bytes += strlen($json);
            $body = json_decode($json, true) ?? [];
            unset($json);

            // Keyed by timestamp: overlapping pages can't duplicate a record.
            $pageLast = [];
            foreach ($body['sensors'] ?? [] as $sensor) {
                $serial = $sensor['sensorSerialNumber'];
                foreach ($sensor['data'] ?? [] as $series) {
                    foreach ($series['records'] ?? [] as [$ts, $value]) {
                        $records[$serial][$ts] = $value;
                        $pageLast[$serial] = max($pageLast[$serial] ?? $ts, $ts);
                    }
                }
            }

            // More in this window: continue after the sensor that got the least
            // far, so a page cut in the middle of the station loses nothing.
            $startMs = ($body['moreResults'] ?? false) && $pageLast
                ? (int) min($pageLast) + 1
                : $endMs;
        }

        return array_map(function (array $series) {
            ksort($series);

            return array_map(fn ($ts, $value) => [$ts, $value], array_keys($series), $series);
        }, $records);
    }

    private function request(): PendingRequest
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('LI-COR is not configured: set LICOR_API_TOKEN.');
        }

        // Retry only what a second attempt can fix: network errors, 429 and 5xx.
        // A rate limit is waited out (Retry-After when given, else 20 s, 40 s,
        // 60 s): two seconds later the cloud still refuses, and a long history
        // load would stop on it.
        return Http::baseUrl(rtrim((string) $this->url, '/'))
            ->withToken($this->token)
            ->acceptJson()
            ->timeout(120)
            ->retry(4, function (int $attempt, $e) {
                if ($e instanceof RequestException && $e->response->status() === 429) {
                    $after = (int) $e->response->header('Retry-After');

                    return 1000 * ($after > 0 ? min($after, 300) : 20 * min($attempt, 3));
                }

                return 2000;
            }, fn ($e) => $e instanceof ConnectionException
                || ($e instanceof RequestException && ($e->response->serverError() || $e->response->status() === 429)));
    }
}
