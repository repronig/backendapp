<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'external_id' => (string) Str::uuid(),
            'documentable_type' => Member::class,
            'documentable_id' => Member::factory(),
            'uploaded_by_user_id' => User::factory(),
            'category' => fake()->randomElement([
                'identity',
                'ownership',
                'compliance',
                'supporting',
            ]),
            'title' => fake()->sentence(3),
            'document_type' => fake()->randomElement([
                'supporting_record',
                'identity_document',
                'ownership_evidence',
                'declaration_attachment',
            ]),
            'visibility' => fake()->randomElement([
                'private',
                'internal',
                'restricted',
            ]),
            'description' => fake()->optional()->sentence(),
            'storage_disk' => fake()->randomElement(['public', 'local', 's3']),
            'checksum' => hash('sha256', Str::uuid()->toString()),
            'last_accessed_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'archived_at' => null,
            'metadata_json' => [
                'original_file_name' => fake()->slug() . '.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => fake()->numberBetween(50_000, 5_000_000),
            ],
        ];
    }
}