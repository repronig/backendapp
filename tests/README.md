# REPRONIG Pest Test Suite

Run the backend tests with:

```bash
composer install
php artisan test
```

The suite uses SQLite in-memory by default through `phpunit.xml` and covers the main contracts for:

- member registration OTP flow
- member onboarding/application validation and documents
- work creation, file uploads, and submission requirements
- admin membership listing contract
- institution listing separation from member data
- roles/permissions seeding
- enum contracts shared by backend and frontend forms
