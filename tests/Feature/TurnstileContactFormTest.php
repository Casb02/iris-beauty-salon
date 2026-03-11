<?php

it('renders the turnstile script and widget on the contact form', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js', false);
    $response->assertSee('class="cf-turnstile"', false);
    $response->assertSee('data-sitekey=', false);
});
