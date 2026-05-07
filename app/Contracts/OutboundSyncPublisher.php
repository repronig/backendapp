<?php

namespace App\Contracts;

interface OutboundSyncPublisher
{
    public function publish(string $domain, array $payload): void;
}
