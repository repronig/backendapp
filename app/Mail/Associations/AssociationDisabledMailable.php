<?php

namespace App\Mail\Associations;

use App\Mail\BaseAppMailable;
use App\Models\Association;

class AssociationDisabledMailable extends BaseAppMailable
{
    public function __construct(public Association $association) {}
    protected function subjectLine(): string { return 'Association Access Disabled'; }
    protected function viewName(): string { return 'emails.associations.disabled'; }
    protected function viewData(): array { return ['association' => $this->association]; }
}
