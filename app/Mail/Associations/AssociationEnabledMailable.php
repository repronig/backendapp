<?php

namespace App\Mail\Associations;

use App\Mail\BaseAppMailable;
use App\Models\Association;

class AssociationEnabledMailable extends BaseAppMailable
{
    public function __construct(public Association $association) {}

    protected function subjectLine(): string
    {
        return 'Association Access Restored';
    }

    protected function viewName(): string
    {
        return 'emails.associations.enabled';
    }

    protected function viewData(): array
    {
        return ['association' => $this->association];
    }
}
