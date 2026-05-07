# JSON API envelope (v1)

All **JSON** success and error responses from `App\Http\Controllers\Api\V1\*` should go through `App\Support\ApiResponse` (`success`, `created`, `paginated`, `error`) on `BaseApiController`.

## Standard shapes

- **Success**: `{ "message": string, "data": … }` — optional extra keys only when documented (avoid one-off shapes).
- **Paginated**: `{ "message", "data", "meta", "links" }` as built by `paginated()`.
- **Error**: `{ "message": string, "errors"?: object }` with appropriate HTTP status (401/403/422/…).

## Exceptions

- **Binary / streaming** responses (CSV exports, file downloads) correctly use `response()->streamDownload()` or similar and do not use the JSON envelope.
- **Webhooks** still return JSON but may include integration-specific `data` fields; keep `message` present for clients that log generically.

## When adding endpoints

1. Return `JsonResponse` from `$this->success|created|paginated|error`.
2. Do not hand-build `response()->json([...])` for normal CRUD unless you are extending the trait with a new helper used everywhere.
