<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Media\UploadUserAvatarAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\UploadAvatarRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvatarController extends BaseApiController
{
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->clearMediaCollection('avatar');

        if (filled($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return $this->success('Avatar removed successfully.', new UserResource($user->fresh()->load('associations')));
    }

    public function store(UploadAvatarRequest $request, UploadUserAvatarAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->file('avatar'));
        return $this->success('Avatar uploaded successfully.', new UserResource($user->load('associations')));
    }
}
