<?php

namespace App\Actions\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadAssociationLogoAction
{
    public function execute(Model $model, UploadedFile $file): Model
    {
        $model->clearMediaCollection('logo');

        if (filled($model->logo_path ?? null)) {
            Storage::disk('public')->delete($model->logo_path);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $fileName = sprintf('association-logo-%s-%s.%s', $model->getKey() ?: 'new', Str::random(12), $extension);
        $path = $file->storeAs('logos', $fileName, 'public');
        Storage::disk('public')->setVisibility($path, 'public');

        $model->forceFill(['logo_path' => $path])->save();

        return $model->fresh();
    }
}
