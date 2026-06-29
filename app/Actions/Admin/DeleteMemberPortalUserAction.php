<?php

namespace App\Actions\Admin;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Works\DeleteWorkAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DeleteMemberPortalUserAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction,
        protected DeleteWorkAction $deleteWorkAction,
    ) {}

    public function execute(
        User $user,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        if ($user->account_type !== 'member' && ! $user->hasRole('member')) {
            throw new HttpException(422, 'Only member portal accounts can be deleted through this action.');
        }

        $user->loadMissing([
            'member.works.files',
            'memberApplication.documents',
        ]);

        $member = $user->member;
        $application = $user->memberApplication;

        $before = [
            'user_id' => $user->id,
            'email' => $user->email,
            'member_id' => $member?->id,
            'application_id' => $application?->id,
            'work_ids' => $member?->works?->pluck('id')->all() ?? [],
        ];

        DB::transaction(function () use ($user, $member, $application, $actor, $ipAddress, $userAgent, $before): void {
            $disk = (string) config('filesystems.default', 'local');

            if ($application) {
                foreach ($application->documents as $document) {
                    if ($document->file_path) {
                        Storage::disk($disk)->delete($document->file_path);
                    }
                }
            }

            if ($member) {
                foreach ($member->works as $work) {
                    $this->deleteWorkAction->execute($work, $actor, $ipAddress, $userAgent);
                }
            }

            $avatarDisk = (string) config('media-library.disk_name', $disk);
            $user->clearMediaCollection('avatar');

            if ($user->avatar_path) {
                Storage::disk($avatarDisk)->delete($user->avatar_path);
            }

            $this->logAuditAction->execute(
                $actor,
                'member_portal_user_deleted',
                $user,
                $before,
                ['deleted_user_id' => $user->id],
                $ipAddress,
                $userAgent
            );

            $user->delete();
        });
    }
}
