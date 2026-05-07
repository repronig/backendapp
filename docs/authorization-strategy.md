# Authorization strategy (REP API)

Single rule set for maintainers: **route middleware decides which _portal_ may call an endpoint**; **policies (+ Spatie permissions where used) decide what a user may do to a resource**.

## Layers

### 1. Role-based route middleware (`routes/api.php`)

- Route groups attach Sanctum + role middleware (`member`, `association_officer`, `institution_user`, `admin`, `super_admin`, etc.).
- **Purpose**: coarse segmentation so the wrong portal cannot reach another portal’s controllers at all (HTTP 403 from middleware).
- **Do not** encode resource-specific rules here (e.g. “can edit only own work”)—that belongs in policies.

### 2. Spatie roles & permissions

- **Roles**: primary labels aligned with portals (`member`, `association_officer`, …).
- **Permissions**: optional finer checks where the UX needs them (`$user->can('…')`).
- Use permissions when multiple capabilities exist inside the same role; keep route-level access role-based unless there is a clear need otherwise.

### 3. Laravel policies (`app/Policies`)

- **Purpose**: authorize actions on concrete models (`view`, `update`, `delete`, …).
- **Default for members / officers / institution users**: model ownership and pivot/cross-role rules live here.

### 4. Admin override (`HandlesAdminOverride` trait)

- Policies that `use HandlesAdminOverride` grant **`admin`** and **`super_admin`** a blanket `before()` pass Policy checks for that policy’s abilities.
- **Intent**: admins operate across tenants without duplicating “if admin” branches in every method.
- **super_admin-only** nuances (e.g. association lifecycle): implement explicitly on the Policy method (see `AssociationPolicy`), not via this trait alone.

## Conflict resolution order

`before()` hooks run first → then middleware has already constrained the portal → Policy method bodies apply for users who did not get an override.

## Practical checklist for new endpoints

1. Correct **middleware group** on the route.
2. **Policy** (`$this->authorize(...)`) where a model instance is acted on—or document why Gate is redundant.
3. Do not rely on frontend-only checks; replicate rules on the API.
