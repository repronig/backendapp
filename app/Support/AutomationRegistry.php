<?php

namespace App\Support;

/**
 * Maps automation definition keys to handler classes. Laravel's scheduler will call enabled
 * definitions in a later epic; handlers stay null until each automation is implemented.
 */
final class AutomationRegistry
{
    /**
     * @return array<string, class-string|null>
     */
    public static function handlers(): array
    {
        return [
            'invoice_reminder' => null,
            'declaration_follow_up' => null,
        ];
    }
}
