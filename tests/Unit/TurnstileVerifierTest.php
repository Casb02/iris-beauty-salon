<?php

use App\Services\TurnstileVerifier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

it('returns true when cloudflare approves the token', function () {
    config()->set('services.turnstile.secret_key', 'test-secret');
    config()->set('services.turnstile.send_remote_ip', false);

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
            'hostname' => 'iris.test',
            'action' => 'submit',
        ], 200),
    ]);

    $verifier = new TurnstileVerifier;

    $result = $verifier->verify('token-123', '203.0.113.9');

    expect($result['success'])->toBeTrue()
        ->and($result['error_codes'])->toBe([])
        ->and($result['hostname'])->toBe('iris.test')
        ->and($result['action'])->toBe('submit');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'test-secret'
            && $request['response'] === 'token-123'
            && ! array_key_exists('remoteip', $request->data());
    });
});

it('returns false when the secret key is not configured', function () {
    config()->set('services.turnstile.secret_key', null);

    Http::fake();

    $verifier = new TurnstileVerifier;

    $result = $verifier->verify('token-123');

    expect($result['success'])->toBeFalse()
        ->and($result['error_codes'])->toBe(['missing-input-secret']);

    Http::assertNothingSent();
});

it('returns false when cloudflare rejects the token', function () {
    config()->set('services.turnstile.secret_key', 'test-secret');

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ], 200),
    ]);

    $verifier = new TurnstileVerifier;

    $result = $verifier->verify('token-123');

    expect($result['success'])->toBeFalse()
        ->and($result['error_codes'])->toBe(['invalid-input-response']);
});

it('sends remote ip when configured', function () {
    config()->set('services.turnstile.secret_key', 'test-secret');
    config()->set('services.turnstile.send_remote_ip', true);

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true], 200),
    ]);

    $verifier = new TurnstileVerifier;
    $verifier->verify('token-123', '203.0.113.9');

    Http::assertSent(function (Request $request): bool {
        return $request['remoteip'] === '203.0.113.9';
    });
});
