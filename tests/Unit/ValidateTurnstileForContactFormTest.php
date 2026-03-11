<?php

use App\Listeners\ValidateTurnstileForContactForm;
use App\Services\TurnstileVerifier;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Statamic\Contracts\Forms\Form as FormContract;
use Statamic\Contracts\Forms\Submission as SubmissionContract;
use Statamic\Events\FormSubmitted;
use Tests\TestCase;

uses(TestCase::class);

afterEach(function () {
    \Mockery::close();
});

function makeFormSubmittedEvent(string $formHandle): FormSubmitted
{
    $form = \Mockery::mock(FormContract::class);
    $form->shouldReceive('handle')->andReturn($formHandle);

    $submission = \Mockery::mock(SubmissionContract::class);
    $submission->shouldReceive('form')->andReturn($form);

    return new FormSubmitted($submission);
}

it('skips verification for non-contact forms', function () {
    config()->set('services.turnstile.site_key', 'site-key');
    config()->set('services.turnstile.secret_key', 'secret-key');

    app()->instance('request', Request::create('/', 'POST', ['cf-turnstile-response' => 'token']));

    $verifier = \Mockery::mock(TurnstileVerifier::class);
    $verifier->shouldNotReceive('verify');

    $listener = new ValidateTurnstileForContactForm($verifier);
    $listener->handle(makeFormSubmittedEvent('newsletter'));

    expect(true)->toBeTrue();
});

it('throws when the turnstile token is missing', function () {
    config()->set('services.turnstile.site_key', 'site-key');
    config()->set('services.turnstile.secret_key', 'secret-key');

    app()->instance('request', Request::create('/', 'POST'));

    $verifier = \Mockery::mock(TurnstileVerifier::class);
    $verifier->shouldNotReceive('verify');

    $listener = new ValidateTurnstileForContactForm($verifier);

    expect(fn () => $listener->handle(makeFormSubmittedEvent('contact')))
        ->toThrow(ValidationException::class);
});

it('throws when turnstile verification fails', function () {
    config()->set('services.turnstile.site_key', 'site-key');
    config()->set('services.turnstile.secret_key', 'secret-key');

    $request = Request::create('/', 'POST', ['cf-turnstile-response' => 'token-123']);
    $request->server->set('REMOTE_ADDR', '203.0.113.20');
    app()->instance('request', $request);

    $verifier = \Mockery::mock(TurnstileVerifier::class);
    $verifier->shouldReceive('verify')
        ->once()
        ->with('token-123', '203.0.113.20')
        ->andReturn([
            'success' => false,
            'error_codes' => ['invalid-input-response'],
            'hostname' => 'iris.test',
            'action' => null,
        ]);

    $listener = new ValidateTurnstileForContactForm($verifier);

    expect(fn () => $listener->handle(makeFormSubmittedEvent('contact')))
        ->toThrow(ValidationException::class);
});

it('allows the submission when turnstile verification succeeds', function () {
    config()->set('services.turnstile.site_key', 'site-key');
    config()->set('services.turnstile.secret_key', 'secret-key');

    $request = Request::create('/', 'POST', ['cf-turnstile-response' => 'token-123']);
    $request->server->set('REMOTE_ADDR', '203.0.113.20');
    app()->instance('request', $request);

    $verifier = \Mockery::mock(TurnstileVerifier::class);
    $verifier->shouldReceive('verify')
        ->once()
        ->with('token-123', '203.0.113.20')
        ->andReturn([
            'success' => true,
            'error_codes' => [],
            'hostname' => 'iris.test',
            'action' => null,
        ]);

    $listener = new ValidateTurnstileForContactForm($verifier);
    $listener->handle(makeFormSubmittedEvent('contact'));

    expect(true)->toBeTrue();
});

it('skips verification when keys are not configured', function () {
    config()->set('services.turnstile.site_key', null);
    config()->set('services.turnstile.secret_key', null);

    app()->instance('request', Request::create('/', 'POST'));

    $verifier = \Mockery::mock(TurnstileVerifier::class);
    $verifier->shouldNotReceive('verify');

    $listener = new ValidateTurnstileForContactForm($verifier);
    $listener->handle(makeFormSubmittedEvent('contact'));

    expect(true)->toBeTrue();
});
