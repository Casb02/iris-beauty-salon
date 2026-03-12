<?php

it('shows a route link below the contact address on the homepage', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('Bekijk route');
    $response->assertSee('https://maps.app.goo.gl/WjbnKg7iP3QmPvtZ6', false);
});
