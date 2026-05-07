<?php

namespace App\Providers;

use App\Services\Mail\MailService;
use App\Support\Notifications\NotificationPreferenceResolver;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailService::class, function ($app) {
            return new MailService(
                $app->make(NotificationPreferenceResolver::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}