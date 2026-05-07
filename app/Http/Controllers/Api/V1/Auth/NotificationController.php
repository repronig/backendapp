<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Notifications\MarkAllNotificationsReadAction;
use App\Actions\Notifications\MarkNotificationReadAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\UserNotificationResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->when($request->boolean('unread'), fn ($query) => $query->whereNull('read_at'))
            ->when($request->filled('category'), function ($query) use ($request) {
                $category = strtolower($request->string('category')->value());
                $driver = DB::connection()->getDriverName();

                if ($driver === 'pgsql') {
                    $query->whereRaw("LOWER(COALESCE((data::jsonb)->>'category', (data::jsonb)->>'type', '')) = ?", [$category]);

                    return;
                }

                if ($driver === 'sqlite') {
                    $query->whereRaw("LOWER(COALESCE(json_extract(data, '$.category'), json_extract(data, '$.type'), '')) = ?", [$category]);

                    return;
                }

                $query->whereRaw("LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(data, '$.category')), JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')), '')) = ?", [$category]);
            })
            ->latest()
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Notifications retrieved successfully.',
            $notifications,
            UserNotificationResource::class
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success('Unread notification count retrieved successfully.', [
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $notification, MarkNotificationReadAction $action): JsonResponse
    {
        try {
            $marked = $action->execute($request->user(), $notification);
        } catch (ModelNotFoundException) {
            return $this->error('Notification not found.', 404);
        }

        return $this->success(
            'Notification marked as read successfully.',
            new UserNotificationResource($marked)
        );
    }

    public function markAllRead(Request $request, MarkAllNotificationsReadAction $action): JsonResponse
    {
        $updated = $action->execute($request->user());

        return $this->success('All notifications marked as read successfully.', [
            'marked_count' => $updated,
        ]);
    }
}
