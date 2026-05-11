<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\AdminSupportTicketReplyRequest;
use App\Http\Requests\Api\V1\StoreSupportTicketInternalNoteRequest;
use App\Http\Requests\Api\V1\UpdateSupportTicketRequest;
use App\Http\Resources\Api\V1\SupportTicketInternalNoteResource;
use App\Http\Resources\Api\V1\SupportTicketReplyResource;
use App\Http\Resources\Api\V1\SupportTicketResource;
use App\Jobs\SendSupportTicketStaffReplyUserNotificationJob;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSupportTicketController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SupportTicket::class);

        $tickets = SupportTicket::query()
            ->with(['user:id,email,first_name,last_name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search')->trim()->value().'%';
                $q->where(function ($inner) use ($term): void {
                    $inner->where('subject', 'ilike', $term)
                        ->orWhere('body', 'ilike', $term);
                });
            })
            ->latest()
            ->paginate($this->perPage($request, 10));

        return $this->paginated(
            'Support tickets retrieved successfully.',
            $tickets,
            SupportTicketResource::class
        );
    }

    public function show(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorize('view', $supportTicket);

        $supportTicket->load([
            'user',
            'replies.user',
            'internalNotes.user',
        ]);

        return $this->success(
            'Support ticket retrieved successfully.',
            new SupportTicketResource($supportTicket)
        );
    }

    public function update(UpdateSupportTicketRequest $request, SupportTicket $supportTicket): JsonResponse
    {
        $supportTicket->update([
            'status' => $request->validated('status'),
        ]);

        $supportTicket->refresh();
        $supportTicket->load(['user', 'replies.user', 'internalNotes.user']);

        return $this->success(
            'Support ticket updated successfully.',
            new SupportTicketResource($supportTicket)
        );
    }

    public function storeReply(AdminSupportTicketReplyRequest $request, SupportTicket $supportTicket): JsonResponse
    {
        $this->authorize('view', $supportTicket);

        $reply = $supportTicket->replies()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
            'is_staff' => true,
        ]);

        $reply->load('user');

        SendSupportTicketStaffReplyUserNotificationJob::dispatch((int) $reply->id)->afterCommit();

        return $this->created(
            'Reply added successfully.',
            new SupportTicketReplyResource($reply)
        );
    }

    public function storeInternalNote(StoreSupportTicketInternalNoteRequest $request, SupportTicket $supportTicket): JsonResponse
    {
        $note = $supportTicket->internalNotes()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $note->load('user');

        return $this->created(
            'Internal note added successfully.',
            new SupportTicketInternalNoteResource($note)
        );
    }
}
