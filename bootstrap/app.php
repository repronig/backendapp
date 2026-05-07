<?php

use App\Http\Middleware\DetectApiRequest;
use App\Http\Middleware\RequireRecentSecurityConfirmation;
use App\Jobs\CleanupExpiredPasswordResetTokensJob;
use App\Jobs\CleanupStalePendingPaymentsJob;
use App\Jobs\Integrations\ProcessIntegrationOutboxJob;
use App\Jobs\Licensing\DispatchInvoiceDueRemindersJob;
use App\Jobs\Licensing\DispatchInvoiceOverdueRemindersJob;
use App\Jobs\Monitoring\MonitorQueueFailuresJob;
use App\Jobs\SyncInvoiceStatusJob;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(DetectApiRequest::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'security.confirmed' => RequireRecentSecurityConfirmation::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->job(new CleanupStalePendingPaymentsJob)->dailyAt('01:00');
        $schedule->job(new SyncInvoiceStatusJob)->dailyAt('02:00');
        $schedule->job(new CleanupExpiredPasswordResetTokensJob)->dailyAt('03:00');
        $schedule->job(new DispatchInvoiceDueRemindersJob)->dailyAt('08:00');
        $schedule->job(new DispatchInvoiceOverdueRemindersJob)->dailyAt('09:00');
        $schedule->job(new MonitorQueueFailuresJob)->hourly();
        $schedule->job(new ProcessIntegrationOutboxJob)->everyFiveMinutes();
        $schedule->command('integrations:outbox-health')->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $shouldRenderApiJson = static function (Request $request): bool {
            return $request->attributes->get('is_api_request', false) === true
                || $request->expectsJson();
        };

        /** @var callable(string,int,?array,?array): JsonResponse $apiError */
        $apiError = static function (
            string $message,
            int $status,
            ?array $errors = null,
            ?array $extra = null
        ): JsonResponse {
            $payload = [
                'message' => $message,
            ];

            if ($errors !== null) {
                $payload['errors'] = $errors;
            }

            if ($extra !== null) {
                $payload = array_merge($payload, $extra);
            }

            return response()->json($payload, $status);
        };

        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) use ($shouldRenderApiJson, $apiError): ?JsonResponse {
            if (! $shouldRenderApiJson($request)) {
                return null;
            }

            return $apiError(
                'The given data was invalid.',
                422,
                $e->errors()
            );
        });

        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) use ($shouldRenderApiJson, $apiError): ?JsonResponse {
            if (! $shouldRenderApiJson($request)) {
                return null;
            }

            return $apiError('Unauthenticated.', 401);
        });

        $exceptions->render(function (
            AuthorizationException $e,
            Request $request
        ) use ($shouldRenderApiJson, $apiError): ?JsonResponse {
            if (! $shouldRenderApiJson($request)) {
                return null;
            }

            return $apiError(
                $e->getMessage() !== '' ? $e->getMessage() : 'This action is unauthorized.',
                403
            );
        });

        $exceptions->render(function (
            ModelNotFoundException|NotFoundHttpException $e,
            Request $request
        ) use ($shouldRenderApiJson, $apiError): ?JsonResponse {
            if (! $shouldRenderApiJson($request)) {
                return null;
            }

            return $apiError('Resource not found.', 404);
        });

        $exceptions->render(function (
            Throwable $e,
            Request $request
        ) use ($shouldRenderApiJson, $apiError): ?JsonResponse {
            if (! $shouldRenderApiJson($request)) {
                return null;
            }

            if ($e instanceof HttpExceptionInterface) {
                return $apiError(
                    $e->getMessage() !== '' ? $e->getMessage() : 'Request failed.',
                    $e->getStatusCode()
                );
            }

            return $apiError('Server error.', 500);
        });
    })
    ->create();
