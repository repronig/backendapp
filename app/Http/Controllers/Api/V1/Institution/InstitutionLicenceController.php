<?php

namespace App\Http\Controllers\Api\V1\Institution;

use App\Actions\Institutions\ResolveInstitutionForUserAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\LicenceResource;
use App\Models\Licence;
use App\Support\Pdf\InstitutionPdfPresenter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class InstitutionLicenceController extends BaseApiController
{
    public function index(Request $request, ResolveInstitutionForUserAction $resolver): JsonResponse
    {
        $institution = $resolver->execute($request->user());

        $licences = Licence::query()
            ->where('institution_id', $institution->id)
            ->with(['institution', 'declaration.faculties', 'invoice', 'payments'])
            ->latest('licence_year')
            ->paginate($this->perPage($request));

        return $this->paginated('Licences retrieved successfully.', $licences, LicenceResource::class);
    }

    public function show(Licence $licence): JsonResponse
    {
        $this->authorize('view', $licence);

        return $this->success(
            'Licence retrieved successfully.',
            new LicenceResource($licence->load(['institution', 'declaration.faculties', 'invoice', 'payments'])->loadCount('payments'))
        );
    }

    public function certificate(Licence $licence, InstitutionPdfPresenter $presenter): Response
    {
        $this->authorize('downloadCertificate', $licence);

        $licence->loadMissing(['institution', 'invoice']);

        $data = $presenter->licenceCertificateData($licence);
        $slug = Str::slug((string) ($licence->licence_number ?? 'licence-'.$licence->id));
        $filename = 'licence-certificate-'.($slug !== '' ? $slug : 'licence-'.$licence->id).'.pdf';

        return Pdf::loadView('pdf.licence-certificate', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }
}
