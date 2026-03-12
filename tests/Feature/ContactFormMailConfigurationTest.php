<?php

use Statamic\Facades\Form;

it('uses the application default mailer for contact form emails', function () {
    $form = Form::find('contact');

    expect($form)->not->toBeNull();

    $emails = $form->email();

    expect($emails)->toBeArray()
        ->and($emails[0]['to'] ?? null)->toBe('info@irisbeautysalon.nl')
        ->and(array_key_exists('mailer', $emails[0]))->toBeFalse();
});
