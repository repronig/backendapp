<?php

namespace App\Actions\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadUserAvatarAction
{
    private function uploadDisk(): string
    {
        return (string) config('media-library.disk_name', config('filesystems.default', 'local'));
    }

    public function execute(Model $model, UploadedFile $file): Model
    {
        $disk = $this->uploadDisk();
        $model->clearMediaCollection('avatar');

        if (filled($model->avatar_path ?? null)) {
            Storage::disk($disk)->delete($model->avatar_path);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $fileName = sprintf('user-avatar-%s-%s.%s', $model->getKey() ?: 'new', Str::random(12), $extension);
        $path = $file->storeAs('avatars', $fileName, $disk);
        Storage::disk($disk)->setVisibility($path, 'public');

        $model->forceFill(['avatar_path' => $path])->save();

        return $model->fresh();
    }
}
