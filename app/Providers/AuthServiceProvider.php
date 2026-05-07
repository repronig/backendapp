<?php

namespace App\Providers;

use App\Models\Association;
use App\Models\Document;
use App\Models\Institution;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\Licence;
use App\Models\LicencePayment;
use App\Models\Member;
use App\Models\MemberApplication;
use App\Models\MemberApplicationDocument;
use App\Models\UsageDeclaration;
use App\Models\Work;
use App\Models\WorkContributor;
use App\Models\WorkFile;
use App\Policies\AssociationPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\InstitutionAnnualDeclarationPolicy;
use App\Policies\InstitutionPolicy;
use App\Policies\LicencePaymentPolicy;
use App\Policies\LicencePolicy;
use App\Policies\MemberApplicationDocumentPolicy;
use App\Policies\MemberApplicationPolicy;
use App\Policies\MemberPolicy;
use App\Policies\UsageDeclarationPolicy;
use App\Policies\WorkContributorPolicy;
use App\Policies\WorkFilePolicy;
use App\Policies\WorkPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\URL;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Association::class => AssociationPolicy::class,
        Document::class => DocumentPolicy::class,
        Member::class => MemberPolicy::class,
        MemberApplication::class => MemberApplicationPolicy::class,
        MemberApplicationDocument::class => MemberApplicationDocumentPolicy::class,
        Work::class => WorkPolicy::class,
        WorkContributor::class => WorkContributorPolicy::class,
        WorkFile::class => WorkFilePolicy::class,
        Institution::class => InstitutionPolicy::class,
        InstitutionAnnualDeclaration::class => InstitutionAnnualDeclarationPolicy::class,
        Licence::class => LicencePolicy::class,
        LicencePayment::class => LicencePaymentPolicy::class,
        UsageDeclaration::class => UsageDeclarationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.frontend_url')."/reset-password?token={$token}&email={$user->email}";
        });

        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });
    }
}
