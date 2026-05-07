# Amazon S3 storage setup

To store uploaded images and files in Amazon S3, configure Laravel's existing `public` disk to use S3.

## 1. Install the S3 filesystem adapter

Run this in the backend project:

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

## 2. Update `.env`

```env
FILESYSTEM_DISK=public
PUBLIC_FILESYSTEM_DRIVER=s3
MEDIA_DISK=public

AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=your-repronig-bucket
AWS_URL=https://your-repronig-bucket.s3.eu-west-1.amazonaws.com
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

For private buckets behind CloudFront, use your CloudFront domain for `AWS_URL`.

## 3. Clear cached config

```bash
php artisan config:clear
php artisan cache:clear
```

## 4. Bucket permissions

Current uploads are treated as public application assets. Configure the bucket or CloudFront distribution so generated file URLs are readable by the frontend.

## Notes

- Existing upload code uses the Laravel `public` disk. This pass makes that disk switchable to S3 using `PUBLIC_FILESYSTEM_DRIVER=s3` without changing controller/action call sites.
- Existing database paths can remain as relative paths such as `user_avatars/file.jpg` or `work-files/file.pdf`.
- The API URL helper now returns S3 URLs when the `public` disk is configured for S3.
