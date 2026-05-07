<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class CleanupExpiredPasswordResetTokensJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('password_reset_tokens')) {
            return;
        }

        DB::table('password_reset_tokens')
            ->where('created_at', '<', now()->subHours(24))
            ->delete();
    }
}
