<?php

namespace App\Jobs\Integrations;

use App\Actions\Integrations\ProcessPendingIntegrationOutboxAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ProcessIntegrationOutboxJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public function __construct(public int $batchSize = 50) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('integration-outbox-process'))
                ->releaseAfter(120)
                ->expireAfter(300),
        ];
    }

    public function handle(ProcessPendingIntegrationOutboxAction $action): void
    {
        $action->execute($this->batchSize);
    }
}
