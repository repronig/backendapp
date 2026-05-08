<?php

namespace Database\Seeders;

use App\Models\Association;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssociationsSeeder extends Seeder
{
    public function run(): void
    {
        $associations = [
            [
                'name' => 'Nigerian Publishers Association',
                'code' => 'NPA',
                'type' => 'publisher_association',
                'description' => 'National association for publishers in Nigeria.',
                'contact_email' => 'info@npa.org',
                'contact_phone' => '+2348000000001',
                'status' => 'active',
            ],
            [
                'name' => 'Association of Nigerian Authors',
                'code' => 'ANA',
                'type' => 'author_association',
                'description' => 'Association for authors and literary professionals in Nigeria.',
                'contact_email' => 'info@ana.org',
                'contact_phone' => '+2348000000002',
                'status' => 'active',
            ],
            [
                'name' => 'Association of Non Fiction Authors of Nigeria',
                'code' => 'ANFAAN',
                'type' => 'non_fiction_author_association',
                'description' => 'Association for non-fiction authors in Nigeria.',
                'contact_email' => 'info@anfaan.org',
                'contact_phone' => '+2348000000003',
                'status' => 'active',
            ],
            [
                'name' => 'Society of Nigerian Artists',
                'code' => 'SNA',
                'type' => 'artist_association',
                'description' => 'Association for visual artists in Nigeria.',
                'contact_email' => 'info@sna.org',
                'contact_phone' => '+2348000000006',
                'status' => 'active',
            ],

        ];

        foreach ($associations as $association) {
            Association::query()->updateOrCreate(
                ['code' => $association['code']],
                [
                    'external_id' => (string) Str::uuid(),
                    'name' => $association['name'],
                    'type' => $association['type'],
                    'description' => $association['description'],
                    'contact_email' => $association['contact_email'],
                    'contact_phone' => $association['contact_phone'],
                    'status' => $association['status'],
                    'country' => 'Nigeria',
                    'is_enabled' => true,
                ]
            );
        }
    }
}
