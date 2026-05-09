<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Actions\MemberOnboarding\CreateMemberApplicationAction;
use App\Actions\MemberOnboarding\SubmitMemberApplicationAction;
use App\Actions\MemberOnboarding\UpdateMemberApplicationAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\StoreMemberApplicationRequest;
use App\Http\Requests\Api\V1\UpdateMemberApplicationRequest;
use App\Http\Resources\Api\V1\MemberApplicationResource;
use App\Models\MemberApplication;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MemberApplicationController extends BaseApiController
{
    public function store(
        StoreMemberApplicationRequest $request,
        CreateMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('create', MemberApplication::class);
        $application = $action->execute(
            $request->user(),
            $request->validated(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->created(
            'Member application created successfully.',
            new MemberApplicationResource($application)
        );
    }

    public function myApplication(Request $request): JsonResponse
    {
        $application = $request->user()->memberApplication?->load([
            'user',
            'association',
            'documents',
        ]);

        return $this->success(
            'Member application retrieved successfully.',
            $application ? new MemberApplicationResource($application) : null
        );
    }

    public function show(MemberApplication $memberApplication): JsonResponse
    {
        $this->authorize('view', $memberApplication);

        return $this->success(
            'Member application retrieved successfully.',
            new MemberApplicationResource(
                $memberApplication->load(['user', 'association', 'documents'])
            )
        );
    }

    public function update(
        UpdateMemberApplicationRequest $request,
        MemberApplication $memberApplication,
        UpdateMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('update', $memberApplication);

        $application = $action->execute(
            $memberApplication,
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Member application updated successfully.',
            new MemberApplicationResource($application)
        );
    }

    public function submit(
        Request $request,
        MemberApplication $memberApplication,
        SubmitMemberApplicationAction $action
    ): JsonResponse {
        $this->authorize('submit', $memberApplication);

        $application = $action->execute(
            $memberApplication,
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success(
            'Member application submitted successfully.',
            new MemberApplicationResource($application)
        );
    }

    public function downloadMandate(MemberApplication $memberApplication): Response
    {
        $this->authorize('view', $memberApplication);

        if (! $memberApplication->isApproved()) {
            abort(422, 'Mandate form can only be downloaded after admin approval.');
        }

        $application = $memberApplication->load(['user', 'association']);

        $ref = $application->application_reference ?: (string) $application->id;
        $safeRef = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $ref) ?: (string) $application->id;
        $filename = sprintf('repronig-member-mandate-%s.pdf', $safeRef);

        $data = [
            'applicationReference' => $application->application_reference ?? 'N/A',
            'applicantName' => $application->user?->name ?? $application->user?->email ?? 'N/A',
            'associationName' => $application->association?->name ?? 'N/A',
            'applicationStatus' => (string) ($application->application_status ?? 'N/A'),
            'affiliationStatus' => (string) ($application->affiliation_status ?? 'N/A'),
            'consentAccepted' => $application->consent_accepted ? 'Yes' : 'No',
            'consentDate' => $application->consent_date?->toDateString() ?? 'N/A',
            'submittedAt' => $application->submitted_at?->toIso8601String() ?? 'N/A',
            'reviewedAt' => $application->reviewed_at?->toIso8601String() ?? 'N/A',
        ];

        return Pdf::loadView('pdf.member-application-mandate', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
