<?php

namespace Database\Seeders;

use App\Enums\AutomationTrigger;
use App\Models\AutomationDefinition;
use Illuminate\Database\Seeder;

class AutomationDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            [
                'key' => 'invoice_reminder',
                'name' => 'Invoice reminder',
                'description' => 'Scheduled reminders for outstanding institution invoices.',
                'trigger' => AutomationTrigger::Schedule,
                'cron' => null,
                'is_enabled' => false,
                'config' => [],
            ],
            [
                'key' => 'declaration_follow_up',
                'name' => 'Declaration follow-up',
                'description' => 'Nudges for incomplete or pending annual declarations.',
                'trigger' => AutomationTrigger::Event,
                'cron' => null,
                'is_enabled' => false,
                'config' => [],
            ],
        ];

        foreach ($definitions as $row) {
            AutomationDefinition::query()->updateOrCreate(
                ['key' => $row['key']],
                $row
            );
        }
    }
}
