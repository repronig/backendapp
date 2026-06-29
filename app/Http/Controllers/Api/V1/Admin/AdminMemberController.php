<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\MemberProfileResource;
use App\Models\Member;
use App\Support\PostgresSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminMemberController extends BaseApiController
{
    protected function queryMembers(Request $request, array $with): Builder
    {
        $members = Member::query()
            ->with($with)
            ->when($request->filled('member_type'), fn ($q) => $q->where('member_type', $request->string('member_type')->value()))
            ->when(
                $request->filled('approval_status') || $request->filled('status'),
                fn ($q) => $q->where('approval_status', $request->string('approval_status', $request->string('status')->value())->value())
            )
            ->when($request->filled('association_id'), fn ($q) => $q->where('association_id', (int) $request->integer('association_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->value();

                $q->where(function ($sub) use ($search) {
                    PostgresSearch::whereColumnIlike($sub, 'member_code', $search);
                    $sub->orWhereHas('user', function ($userQuery) use ($search) {
                        PostgresSearch::whereAnyColumnIlike($userQuery, ['first_name', 'last_name', 'email'], $search);
                    });
                });
            });

        $this->applyDateRange($members, $request, 'joined_at');
        $this->applySorting($members, $request, ['joined_at', 'created_at', 'approval_status', 'member_code'], 'joined_at');

        return $members;
    }

    public function index(Request $request): JsonResponse
    {
        $members = $this->queryMembers($request, ['user.roles', 'association', 'profile'])
            ->paginate($this->perPage($request));

        return $this->paginated(
            'Members retrieved successfully.',
            $members,
            MemberProfileResource::class
        );
    }

    public function show(Member $member): JsonResponse
    {
        return $this->success(
            'Member retrieved successfully.',
            new MemberProfileResource(
                $member->load(['user.roles', 'association', 'profile'])
            )
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->queryMembers($request, ['user', 'association'])->get();

        $filename = 'members_export_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Member code', 'Member type', 'Approval status', 'User name', 'User email', 'Association', 'Joined at']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->member_code,
                    $row->member_type,
                    $row->approval_status,
                    optional($row->user)->name,
                    optional($row->user)->email,
                    optional($row->association)->name,
                    optional($row->joined_at)?->toDateString(),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function destroy(
        Member $member,
        \App\Actions\Admin\DeleteMemberPortalUserAction $action,
        \Illuminate\Http\Request $request
    ): JsonResponse {
        $member->loadMissing('user');
        $user = $member->user;
        abort_unless($user, 404, 'Member user account not found.');

        $this->authorize('delete', $member);

        $action->execute($user, $request->user(), $request->ip(), $request->userAgent());

        return $this->success('Member and associated records deleted successfully.');
    }
}
