<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreSupportTicketReplyRequest;
use App\Http\Requests\Api\V1\StoreSupportTicketRequest;
use App\Http\Resources\Api\V1\SupportTicketReplyResource;
use App\Http\Resources\Api\V1\SupportTicketResource;
use App\Jobs\SendSupportTicketSubmittedNotificationsJob;
use App\Jobs\SendSupportTicketUserReplyAdminNotificationsJob;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportTicketController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SupportTicket::class);

        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->latest()
            ->paginate($this->perPage($request, 10));

        return $this->paginated(
            'Support tickets retrieved successfully.',
            $tickets,
            SupportTicketResource::class
        );
    }

    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $data = $request->validated();

        $ticket = SupportTicket::query()->create([
            'user_id' => $request->user()->id,
            'portal_context' => $data['portal_context'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'category' => $data['category'],
        ]);

        $ticket->load('user');

        SendSupportTicketSubmittedNotificationsJob::dispatch((int) $ticket->id)->afterCommit();

        return $this->created(
            'Support ticket created successfully.',
            new SupportTicketResource($ticket)
        );
    }

    public function show(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorize('view', $supportTicket);

        $supportTicket->load([
            'user',
            'replies.user',
        ]);

        return $this->success(
            'Support ticket retrieved successfully.',
            new SupportTicketResource($supportTicket)
        );
    }

    public function storeReply(StoreSupportTicketReplyRequest $request, SupportTicket $supportTicket): JsonResponse
    {
        if ($supportTicket->user_id !== $request->user()->id) {
            abort(403, 'Only the ticket owner may reply from this endpoint.');
        }

        $reply = $supportTicket->replies()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
            'is_staff' => false,
        ]);

        $reply->load('user');

        SendSupportTicketUserReplyAdminNotificationsJob::dispatch((int) $reply->id)->afterCommit();

        return $this->created(
            'Reply added successfully.',
            new SupportTicketReplyResource($reply)
        );
    }
}
