<?php

namespace App\Jobs\Imports;

use App\Actions\AssociationReview\ApproveMemberApplicationAction;
use App\Actions\Imports\ImportWorkAction;
use App\Actions\MemberOnboarding\CreateMemberApplicationAction;
use App\Actions\MemberOnboarding\SubmitMemberApplicationAction;
use App\Models\Association;
use App\Models\ImportBatch;
use App\Models\Institution;
use App\Models\Member;
use App\Models\MemberApplicationDocument;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProcessCsvImportJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $importBatchId,
        public string $importType,
        public string $path,
        public string $mode = 'preview',
    ) {}

    public function handle(): void
    {
        $batch = ImportBatch::query()->find($this->importBatchId);

        if (! $batch || ! Storage::exists($this->path)) {
            return;
        }

        $rows = array_map('str_getcsv', preg_split('/\r\n|\r|\n/', trim((string) Storage::get($this->path))));
        $header = array_map(fn ($value) => strtolower(trim((string) $value)), array_shift($rows) ?: []);

        $required = match ($this->importType) {
            'members' => ['first_name', 'last_name', 'email'],
            'works' => ['title', 'member_email'],
            'institutions' => ['name', 'email', 'institution_type'],
            default => [],
        };

        $batch->failures()->delete();

        $valid = 0;
        $invalid = 0;
        $processed = 0;
        $failures = [];

        foreach ($rows as $index => $row) {
            if ($row === [null] || $row === []) {
                continue;
            }

            $payload = array_combine($header, array_pad($row, count($header), null)) ?: [];
            $errors = $this->validatePayload($payload, $required);

            if ($errors !== []) {
                $invalid++;
                $failure = $batch->failures()->create([
                    'row_number' => $index + 2,
                    'row_payload_json' => $payload,
                    'errors_json' => $errors,
                ]);
                $failures[] = [$failure->row_number, json_encode($payload), json_encode($errors)];

                continue;
            }

            $valid++;

            if ($this->mode === 'process') {
                try {
                    DB::transaction(function () use ($payload): void {
                        match ($this->importType) {
                            'members' => $this->processMember($payload),
                            'works' => $this->processWork($payload),
                            'institutions' => $this->processInstitution($payload),
                            default => null,
                        };
                    });

                    $processed++;
                } catch (\Throwable $throwable) {
                    $invalid++;
                    $failure = $batch->failures()->create([
                        'row_number' => $index + 2,
                        'row_payload_json' => $payload,
                        'errors_json' => ['row' => [$throwable->getMessage()]],
                    ]);
                    $failures[] = [$failure->row_number, json_encode($payload), json_encode(['row' => [$throwable->getMessage()]])];
                }
            }
        }

        $status = $this->mode === 'process'
            ? ($invalid > 0 ? 'processed_with_errors' : 'processed')
            : 'validated';

        $batch->update([
            'status' => $status,
            'valid_rows' => $valid,
            'invalid_rows' => $invalid,
            'processed_rows' => $this->mode === 'process' ? $processed : $valid + $invalid,
            'validated_at' => now(),
            'processed_at' => $this->mode === 'process' ? now() : null,
            'summary_json' => [
                'mode' => $this->mode,
                'required_columns' => $required,
                'source_path' => $this->path,
            ],
        ]);

        if ($failures !== []) {
            $reportPath = 'imports/reports/import_'.$batch->id.'_errors.csv';
            $contents = "row_number,row_payload,errors\n";
            foreach ($failures as $failure) {
                $contents .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', $failure))."\n";
            }
            Storage::put($reportPath, $contents);
            $batch->update(['error_report_path' => $reportPath]);
        }
    }

    protected function validatePayload(array $payload, array $required): array
    {
        $errors = [];
        foreach ($required as $column) {
            if (blank($payload[$column] ?? null)) {
                $errors[$column][] = 'This field is required.';
            }
        }

        return $errors;
    }

    protected function processMember(array $payload): void
    {
        $email = strtolower(trim((string) $payload['email']));
        $phoneRaw = $payload['phone'] ?? null;
        $phone = is_string($phoneRaw) ? trim($phoneRaw) : null;
        $phone = ($phone === '' || $phone === null) ? null : $phone;

        if ($phone !== null && User::query()->where('phone', $phone)->where('email', '!=', $email)->exists()) {
            throw new \RuntimeException('This phone number is already assigned to another user account.');
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'first_name' => (string) $payload['first_name'],
                'last_name' => (string) $payload['last_name'],
                'phone' => $phone,
                'account_type' => 'member',
                'status' => 'active',
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
            ]
        );

        if (method_exists($user, 'assignRole') && ! $user->hasRole('member')) {
            $user->assignRole('member');
        }

        if ($user->member) {
            return;
        }

        $association = null;
        if (! blank($payload['association_code'] ?? null)) {
            $association = Association::query()->where('code', (string) $payload['association_code'])->first();
        }

        $application = app(CreateMemberApplicationAction::class)->execute(
            $user,
            [
                'association_id' => $association?->id,
                'applicant_type' => $payload['member_type'] ?? 'author',
                'nationality' => $payload['nationality'] ?? 'Nigerian',
                'country_of_residence' => $payload['country_of_residence'] ?? 'Nigeria',
                'is_diaspora' => false,
                'notes' => 'Imported from CSV batch.',
            ],
            '127.0.0.1',
            'ProcessCsvImportJob'
        );

        MemberApplicationDocument::factory()->pending()->create([
            'member_application_id' => $application->id,
            'uploaded_by_user_id' => $user->id,
            'document_type' => 'proof_of_id',
        ]);

        MemberApplicationDocument::factory()->pending()->create([
            'member_application_id' => $application->id,
            'uploaded_by_user_id' => $user->id,
            'document_type' => 'proof_of_address',
        ]);

        $submitted = app(SubmitMemberApplicationAction::class)->execute($application->fresh('documents'), $user, '127.0.0.1', 'ProcessCsvImportJob');

        app(ApproveMemberApplicationAction::class)->execute(
            $submitted,
            $this->importReviewer(),
            'Approved through CSV import flow.',
            '127.0.0.1',
            'ProcessCsvImportJob'
        );

        $member = Member::query()->where('user_id', $user->id)->first();
        if ($member) {
            $member->update([
                'member_code' => $payload['member_code'] ?? $member->member_code,
                'member_type' => $payload['member_type'] ?? $member->member_type,
                'account_status' => 'active',
            ]);
        }
    }

    protected function processWork(array $payload): void
    {
        $user = User::query()->where('email', strtolower(trim((string) $payload['member_email'])))->first();
        $member = $user?->member;

        if (! $member) {
            throw new \RuntimeException('Member not found for supplied member_email.');
        }

        app(ImportWorkAction::class)->execute($member, $payload, $this->importReviewer(), '127.0.0.1', 'ProcessCsvImportJob');
    }

    protected function processInstitution(array $payload): void
    {
        $allowedOnboardingStatuses = ['draft', 'submitted', 'under_review', 'approved', 'rejected'];
        $allowedAccountStatuses = ['pending_review', 'active', 'suspended', 'blocked', 'inactive'];
        $allowedGovernanceStatuses = ['normal', 'restricted', 'suspended', 'blocked'];

        $onboardingStatus = (string) ($payload['onboarding_status'] ?? 'approved');
        $accountStatus = (string) ($payload['account_status'] ?? 'active');
        $governanceStatus = (string) ($payload['governance_status'] ?? 'normal');

        if (! in_array($onboardingStatus, $allowedOnboardingStatuses, true)) {
            throw new \RuntimeException('Invalid onboarding_status supplied.');
        }

        if (! in_array($accountStatus, $allowedAccountStatuses, true)) {
            throw new \RuntimeException('Invalid account_status supplied.');
        }

        if (! in_array($governanceStatus, $allowedGovernanceStatuses, true)) {
            throw new \RuntimeException('Invalid governance_status supplied.');
        }

        Institution::query()->updateOrCreate(
            ['email' => strtolower(trim((string) $payload['email']))],
            [
                'name' => (string) $payload['name'],
                'institution_type' => (string) $payload['institution_type'],
                'phone' => $payload['phone'] ?? null,
                'contact_person_name' => $payload['contact_person_name'] ?? null,
                'contact_person_title' => $payload['contact_person_title'] ?? null,
                'onboarding_status' => $onboardingStatus,
                'account_status' => $accountStatus,
                'governance_status' => $governanceStatus,
                'address_line_1' => $payload['address_line_1'] ?? null,
                'address_line_2' => $payload['address_line_2'] ?? null,
                'country' => $payload['country'] ?? 'NG',
                'postal_code' => $payload['postal_code'] ?? null,
            ]
        );
    }

    protected function importReviewer(): User
    {
        $reviewer = User::query()->firstOrCreate(
            ['email' => 'imports.system@repronig.local'],
            [
                'first_name' => 'Imports',
                'last_name' => 'System',
                'account_type' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'password' => Hash::make('Password123!'),
            ]
        );

        if (method_exists($reviewer, 'assignRole') && ! $reviewer->hasRole('admin')) {
            $reviewer->assignRole('admin');
        }

        return $reviewer;
    }
}
