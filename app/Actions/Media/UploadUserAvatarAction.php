<?php

namespace App\Actions\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadUserAvatarAction
{
    public function execute(Model $model, UploadedFile $file): Model
    {
        $model->clearMediaCollection('avatar');

        if (filled($model->avatar_path ?? null)) {
            Storage::disk('public')->delete($model->avatar_path);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $fileName = sprintf('user-avatar-%s-%s.%s', $model->getKey() ?: 'new', Str::random(12), $extension);
        $path = $file->storeAs('avatars', $fileName, 'public');
        Storage::disk('public')->setVisibility($path, 'public');

        $model->forceFill(['avatar_path' => $path])->save();

        return $model->fresh();
    }
}
