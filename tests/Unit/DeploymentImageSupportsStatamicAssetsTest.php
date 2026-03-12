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
        ->and($startupScript)->toContain('storage/app/public')
        ->and($startupScript)->toContain('set_runtime_permissions()')
        ->and(substr_count($startupScript, 'set_runtime_permissions'))->toBeGreaterThanOrEqual(3)
        ->and($startupScript)->toContain('php artisan statamic:stache:clear --no-interaction || true')
        ->and($startupScript)->toContain('unitd --control unix:/run/control.unit.sock --no-daemon')
        ->and($startupScript)->toContain('curl --silent --show-error --fail')
        ->and($startupScript)->toContain('--unix-socket /run/control.unit.sock')
        ->and($startupScript)->toContain('@/docker-entrypoint.d/unit.json');
});
