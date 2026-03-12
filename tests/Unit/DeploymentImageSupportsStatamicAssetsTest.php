<?php

it('installs the php extensions statamic assets need in the production image', function () {
    $dockerfile = file_get_contents(__DIR__.'/../../Dockerfile');

    expect($dockerfile)->not->toBeFalse()
        ->and($dockerfile)->toContain('exif')
        ->and($dockerfile)->toContain('gd')
        ->and($dockerfile)->toContain('mbstring');
});

it('prepares the coolify-mounted asset directory at container startup', function () {
    $startupScript = file_get_contents(__DIR__.'/../../docker/start-container.sh');

    expect($startupScript)->not->toBeFalse()
        ->and($startupScript)->toContain('public/assets')
        ->and($startupScript)->toContain('storage/app/public');
});
