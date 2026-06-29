<?php

return [
    'max_rows_per_batch' => (int) env('MEMBER_WORK_IMPORT_MAX_ROWS', 5000),
    'max_csv_size_kb' => (int) env('MEMBER_WORK_IMPORT_MAX_CSV_KB', 10240),
    'max_zip_size_kb' => (int) env('MEMBER_WORK_IMPORT_MAX_ZIP_KB', 512000),
    'uploads_per_hour' => (int) env('MEMBER_WORK_IMPORT_UPLOADS_PER_HOUR', 5),
    'submit_ready_per_hour' => (int) env('MEMBER_WORK_IMPORT_SUBMIT_PER_HOUR', 1),
    'enabled' => filter_var(env('MEMBER_WORK_IMPORT_ENABLED', true), FILTER_VALIDATE_BOOL),
];
