<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Read-only client for the partner's ThingsBoard REST API. Only covers what
 * the sync needs: log in, list the devices the account can see, and read
 * their telemetry.
 */
class ThingsBoardClient
{
    private ?string $token = null;

    public function __construct(
        private readonly ?string $url,
        private readonly ?string $username,
        private readonly ?string $password,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            config('thingsboard.url'),
            config('thingsboard.username'),
            config('thingsboard.password'),
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->url) && filled($this->username) && filled($this->password);
    }

    /** The logged-in account: authority (TENANT_ADMIN / CUSTOMER_USER), customerId, ... */
    public function currentUser(): array
    {
        return $this->request()->get('/api/auth/user')->throw()->json();
    }

    /**
     * Every device visible to the account. Tenant admins and customer users
     * list devices through different endpoints, so the authority is resolved
     * first.
     */
    public function devices(): array
    {
        $user = $this->currentUser();

        $path = ($user['authority'] ?? null) === 'TENANT_ADMIN'
            ? '/api/tenant/devices'
            : '/api/customer/' . ($user['customerId']['id'] ?? '') . '/devices';

        $devices = [];
        $page    = 0;

        do {
            $body = $this->request()
                ->get($path, ['pageSize' => 100, 'page' => $page])
                ->throw()
                ->json();

            $devices = array_merge($devices, $body['data'] ?? []);
            $page++;
        } while ($body['hasNext'] ?? false);

        return $devices;
    }

    /** Names of the timeseries keys a device has ever reported. */
    public function telemetryKeys(string $deviceId): array
    {
        return $this->request()
            ->get("/api/plugins/telemetry/DEVICE/{$deviceId}/keys/timeseries")
            ->throw()
            ->json() ?? [];
    }

    /**
     * Latest value per key, as [key => ['ts' => epoch ms, 'value' => mixed]].
     * With no $keys, ThingsBoard returns every key of the device.
     */
    public function latestTelemetry(string $deviceId, array $keys = []): array
    {
        $body = $this->request()
            ->get("/api/plugins/telemetry/DEVICE/{$deviceId}/values/timeseries", array_filter([
                'keys' => implode(',', $keys),
            ]))
            ->throw()
            ->json() ?? [];

        return array_map(fn (array $points) => $points[0] ?? null, $body);
    }

    /**
     * Raw points per key between two epoch-ms instants, oldest first, as
     * [key => [['ts' => epoch ms, 'value' => mixed], ...]]. $limit applies
     * per key, so a long gap is caught up over several calls.
     */
    public function timeseries(string $deviceId, array $keys, int $startTs, int $endTs, int $limit): array
    {
        return $this->request()
            ->get("/api/plugins/telemetry/DEVICE/{$deviceId}/values/timeseries", [
                'keys'    => implode(',', $keys),
                'startTs' => $startTs,
                'endTs'   => $endTs,
                'limit'   => $limit,
                'orderBy' => 'ASC',
            ])
            ->throw()
            ->json() ?? [];
    }

    private function request(): PendingRequest
    {
        return $this->http()->withHeaders([
            'X-Authorization' => 'Bearer ' . $this->token(),
        ]);
    }

    private function token(): string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        if (!$this->isConfigured()) {
            throw new RuntimeException('ThingsBoard is not configured: set THINGSBOARD_URL, THINGSBOARD_USERNAME and THINGSBOARD_PASSWORD.');
        }

        $token = $this->http()
            ->post('/api/auth/login', [
                'username' => $this->username,
                'password' => $this->password,
            ])
            ->throw()
            ->json('token');

        if (!$token) {
            throw new RuntimeException('ThingsBoard login succeeded but returned no token.');
        }

        return $this->token = $token;
    }

    private function http(): PendingRequest
    {
        // Retry only what a second attempt can fix: network errors and 5xx.
        return Http::baseUrl(rtrim((string) $this->url, '/'))
            ->acceptJson()
            ->timeout(15)
            ->retry(3, 1000, fn ($e) => $e instanceof ConnectionException
                || ($e instanceof RequestException && $e->response->serverError()));
    }
}
