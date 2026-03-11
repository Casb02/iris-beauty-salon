<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class TurnstileVerifier
{
    /**
     * @return array{
     *     success: bool,
     *     error_codes: array<int, string>,
     *     hostname: string|null,
     *     action: string|null
     * }
     */
    public function verify(string $token, ?string $ipAddress = null): array
    {
        $secretKey = config('services.turnstile.secret_key');

        if (! is_string($secretKey) || blank($secretKey)) {
            return [
                'success' => false,
                'error_codes' => ['missing-input-secret'],
                'hostname' => null,
                'action' => null,
            ];
        }

        $payload = [
            'secret' => $secretKey,
            'response' => $token,
        ];

        if ((bool) config('services.turnstile.send_remote_ip', false) && filled($ipAddress)) {
            $payload['remoteip'] = $ipAddress;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(5)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $payload);
        } catch (ConnectionException) {
            return [
                'success' => false,
                'error_codes' => ['internal-error'],
                'hostname' => null,
                'action' => null,
            ];
        }

        if (! $response->successful()) {
            return [
                'success' => false,
                'error_codes' => ['bad-request'],
                'hostname' => null,
                'action' => null,
            ];
        }

        $responseData = $response->json();

        $errorCodes = collect(data_get($responseData, 'error-codes', []))
            ->filter(fn (mixed $errorCode): bool => is_string($errorCode) && filled($errorCode))
            ->values()
            ->all();

        $hostname = data_get($responseData, 'hostname');
        $action = data_get($responseData, 'action');

        return [
            'success' => (bool) data_get($responseData, 'success', false),
            'error_codes' => $errorCodes,
            'hostname' => is_string($hostname) ? $hostname : null,
            'action' => is_string($action) ? $action : null,
        ];
    }
}
