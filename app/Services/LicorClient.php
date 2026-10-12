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
    ) {}

    public static function fromConfig(): self
    {
        return new self(config('licor.url'), config('licor.token'));
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
     * sensor. Keep the range to about a day: a station logs ~43k records a day.
     */
    public function records(string $deviceSerial, int $startMs, int $endMs): array
    {
        $records = [];

        while ($startMs < $endMs) {
            $body = $this->request()->get('/data', [
                'deviceSerialNumber' => $deviceSerial,
                'startTime'          => $startMs,
                'endTime'            => $endMs,
            ])->throw()->json();

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
        return Http::baseUrl(rtrim((string) $this->url, '/'))
            ->withToken($this->token)
            ->acceptJson()
            ->timeout(120)
            ->retry(3, 2000, fn ($e) => $e instanceof ConnectionException
                || ($e instanceof RequestException && ($e->response->serverError() || $e->response->status() === 429)));
    }
}
