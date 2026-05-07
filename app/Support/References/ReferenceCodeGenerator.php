<?php

namespace App\Support\References;

use App\Models\Invoice;
use App\Models\Licence;
use App\Models\LicencePayment;
use App\Models\Member;
use App\Models\MemberApplication;
use App\Models\Work;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RuntimeException;

class ReferenceCodeGenerator
{
    public function generateWorkReferenceNumber(): string
    {
        return $this->generateTimeBasedReference(Work::class, 'reference_number', 'WRK-', true);
    }

    public function generateMemberCode(): string
    {
        return $this->generateTimeBasedReference(Member::class, 'member_code', 'RM-', true);
    }

    public function generateMemberApplicationReference(): string
    {
        return $this->generateShortUnique(MemberApplication::class, 'application_reference', 'RAR-');
    }

    public function generateInvoiceNumber(int $licensingYear, int $institutionId): string
    {
        unset($licensingYear, $institutionId);

        return $this->generateTimeBasedReference(Invoice::class, 'invoice_number', 'INV-', false);
    }

    public function generateLicenceNumber(string $licenceId, int $licensingYear): string
    {
        unset($licenceId, $licensingYear);

        return $this->generateTimeBasedReference(
            Licence::class,
            'licence_number',
            'RL-',
            true
        );
    }

    public function generatePaymentReference(): string
    {
        return $this->generateTimeBasedReference(LicencePayment::class, 'payment_reference', 'PAY-', false);
    }

    /**
     * Pattern:
     * - PAY / INV => {PREFIX}{yy}{mm}{dd}{last4(unix timestamp)}
     * - LIC       => RL-{yy}{mm}{dd}{last4(unix timestamp)}{AA}
     * - MEMBER    => RM-{yy}{mm}{dd}{last4(unix timestamp)}{AA}
     *
     * @param  class-string<Model>  $modelClass
     */
    protected function generateTimeBasedReference(string $modelClass, string $column, string $prefix, bool $includeRandomLetters): string
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $candidate = $prefix
                .$this->buildDateSegment()
                .$this->buildUnixLastFourSegment()
                .($includeRandomLetters ? Str::upper(Str::random(2)) : '');

            if (! $modelClass::query()->where($column, $candidate)->exists()) {
                return $candidate;
            }

            // Same-second collisions can occur; wait a bit for a new unix timestamp tail.
            usleep(200_000);
        }

        throw new RuntimeException(sprintf('Unable to generate time-based reference for %s.%s', $modelClass, $column));
    }

    protected function buildDateSegment(): string
    {
        return now()->format('ymd');
    }

    protected function buildUnixLastFourSegment(): string
    {
        $tail = substr((string) now()->timestamp, -4);

        return str_pad($tail, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function generateShortReference(string $modelClass, string $column, string $prefix, int $suffixLength = 13): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $suffix = Str::upper(substr(Str::ulid()->toBase32(), 0, $suffixLength));
            $candidate = $prefix.$suffix;

            if (! $modelClass::query()->where($column, $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException(sprintf('Unable to generate short reference for %s.%s', $modelClass, $column));
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function generateUnique(string $modelClass, string $column, string $prefix): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = $prefix.Str::upper(Str::ulid()->toBase32());

            if (! $modelClass::query()->where($column, $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException(sprintf('Unable to generate unique reference for %s.%s', $modelClass, $column));
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    protected function generateReadableUnique(string $modelClass, string $column, string $prefix = ''): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = $prefix
                .random_int(1000, 9999)
                .Str::upper(Str::random(3))
                .random_int(100000, 999999)
                .Str::upper(Str::random(4));
            $candidate = preg_replace('/[^A-Z0-9-]/', '', $candidate) ?: $prefix.Str::upper(Str::random(18));

            if (! $modelClass::query()->where($column, $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException(sprintf('Unable to generate readable unique reference for %s.%s', $modelClass, $column));
    }

    protected function generateShortUnique(string $modelClass, string $column, string $prefix = ''): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $candidate = $prefix.Str::upper(Str::random(10));
            $candidate = preg_replace('/[^A-Z0-9-]/', '', $candidate) ?: $prefix.Str::upper(Str::random(10));
            $candidate = Str::limit($candidate, 16, '');

            if (! $modelClass::query()->where($column, $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException(sprintf('Unable to generate unique short reference for %s.%s', $modelClass, $column));
    }
}
