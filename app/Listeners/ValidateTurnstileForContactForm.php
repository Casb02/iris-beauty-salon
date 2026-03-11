<?php

namespace App\Listeners;

use App\Services\TurnstileVerifier;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Statamic\Events\FormSubmitted;

class ValidateTurnstileForContactForm
{
    public function __construct(public TurnstileVerifier $turnstileVerifier) {}

    public function handle(FormSubmitted $event): void
    {
        if ($event->submission->form()->handle() !== 'contact') {
            return;
        }

        $siteKey = config('services.turnstile.site_key');
        $secretKey = config('services.turnstile.secret_key');

        if (! is_string($siteKey) || blank($siteKey) || ! is_string($secretKey) || blank($secretKey)) {
            return;
        }

        $token = request()->input('cf-turnstile-response');

        if (! is_string($token) || blank($token)) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Bevestig dat je geen robot bent.',
            ]);
        }

        $verification = $this->turnstileVerifier->verify($token, request()->ip());

        if (($verification['success'] ?? false) === true) {
            return;
        }

        $errorCodes = collect($verification['error_codes'] ?? [])
            ->filter(fn (mixed $errorCode): bool => is_string($errorCode) && filled($errorCode))
            ->values()
            ->all();

        Log::warning('Turnstile verification failed for contact form.', [
            'error_codes' => $errorCodes,
            'hostname' => $verification['hostname'] ?? null,
            'action' => $verification['action'] ?? null,
            'ip' => request()->ip(),
        ]);

        $message = 'Turnstile verificatie is mislukt. Probeer het opnieuw.';

        if (in_array('timeout-or-duplicate', $errorCodes, true)) {
            $message = 'Turnstile token is verlopen of al gebruikt. Probeer het opnieuw.';
        } elseif (in_array('invalid-input-secret', $errorCodes, true) || in_array('missing-input-secret', $errorCodes, true)) {
            $message = 'Turnstile configuratie is ongeldig. Neem contact op met de beheerder.';
        } elseif (in_array('missing-input-response', $errorCodes, true)) {
            $message = 'Bevestig dat je geen robot bent.';
        }

        if (config('app.debug') && $errorCodes !== []) {
            $message .= ' ('.implode(', ', $errorCodes).')';
        }

        throw ValidationException::withMessages([
            'cf-turnstile-response' => $message,
        ]);
    }
}
