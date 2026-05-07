<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Actions\Licensing\BuildInstitutionLicensingSummaryAction;
use App\Actions\Licensing\ResolveInstitutionByLicenceIdAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\InitiateLicencePaymentRequest;
use App\Http\Resources\Api\V1\InstitutionLicensingSummaryResource;
use App\Http\Resources\Api\V1\PaymentInitiationResource;
use App\Actions\Licensing\InitiateLicencePaymentAction;
use Illuminate\Http\JsonResponse;

class LicenceLookupController extends BaseApiController
{
    public function show(
        string $licenceId,
        ResolveInstitutionByLicenceIdAction $resolveInstitutionByLicenceIdAction,
        BuildInstitutionLicensingSummaryAction $buildInstitutionLicensingSummaryAction
    ): JsonResponse {
        $institution = $resolveInstitutionByLicenceIdAction->execute($licenceId);
        $summary = $buildInstitutionLicensingSummaryAction->execute($institution);

        return $this->success('Licence summary retrieved successfully.', new InstitutionLicensingSummaryResource($summary));
    }

    public function initializePayment(
        InitiateLicencePaymentRequest $request,
        ResolveInstitutionByLicenceIdAction $resolveInstitutionByLicenceIdAction,
        InitiateLicencePaymentAction $initiateLicencePaymentAction
    ): JsonResponse {
        $institution = $resolveInstitutionByLicenceIdAction->execute((string) $request->validated('licence_id'));
        $licence = $institution->licences()
            ->where('licence_year', (int) ($request->validated('licensing_year') ?? now()->year))
            ->latest('id')
            ->firstOrFail();

        $result = $initiateLicencePaymentAction->execute(
            $licence,
            null,
            (float) $request->validated('amount'),
            null,
            (string) $request->validated('gateway_name', 'paystack'),
            $request->validated('callback_url'),
            $request->ip(),
            $request->userAgent()
        );

        return $this->success('Payment initiated successfully.', new PaymentInitiationResource($result));
    }
}
