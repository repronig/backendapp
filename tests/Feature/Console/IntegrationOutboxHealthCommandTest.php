<?php

it('runs the integration outbox health check command', function () {
    $this->artisan('integrations:outbox-health')->assertSuccessful();
});
