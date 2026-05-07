<?php

namespace App\Actions\Licensing;

use App\Models\Institution;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ResolveInstitutionByLicenceIdAction
{
    public function execute(string $licenceId): Institution
    {
        $institution = Institution::query()
            ->where('licence_id', $licenceId)
            ->first();

        if (! $institution) {
            throw (new ModelNotFoundException())->setModel(Institution::class, [$licenceId]);
        }

        return $institution;
    }
}
