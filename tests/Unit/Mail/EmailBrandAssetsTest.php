<?php

use App\Support\Mail\EmailBrandAssets;

it('returns a data-uri logo when repronig-logo.png exists in public', function () {
    expect(public_path('repronig-logo.png'))->toBeReadableFile()
        ->and(EmailBrandAssets::logoImgSrc())->toStartWith('data:image/png;base64,');
});

it('strips trailing /api/v1 from app url for public web base', function () {
    config(['mail.asset_base_url' => null]);
    config(['app.url' => 'https://api.example.org/api/v1']);

    expect(EmailBrandAssets::publicWebBaseUrl())->toBe('https://api.example.org');
});

it('uses MAIL_ASSET_BASE_URL when configured', function () {
    config(['mail.asset_base_url' => 'https://cdn.example.com/assets']);

    expect(EmailBrandAssets::publicWebBaseUrl())->toBe('https://cdn.example.com/assets');
});
