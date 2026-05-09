<?php

use App\Support\PublicAssetUrl;

it('strips mistaken /storage segment from S3-style public URLs', function () {
    $wrong = 'https://repronig-staging.s3.eu-north-1.amazonaws.com/storage/documents/rs6FjmjnqMQMS2vmKAfuDLGZbTMpy3zvi3UhE7R9.png';
    $fixed = 'https://repronig-staging.s3.eu-north-1.amazonaws.com/documents/rs6FjmjnqMQMS2vmKAfuDLGZbTMpy3zvi3UhE7R9.png';

    expect(PublicAssetUrl::normalizeRemotePublicObjectUrl($wrong))->toBe($fixed);
});

it('does not alter correct remote URLs', function () {
    $ok = 'https://repronig-staging.s3.eu-north-1.amazonaws.com/documents/file.png';

    expect(PublicAssetUrl::normalizeRemotePublicObjectUrl($ok))->toBe($ok);
});
