<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class MediaStorage
{
    public static function diskName(): string
    {
        return (string) config('filesystems.media_disk', 'local');
    }

    public static function disk(): Filesystem
    {
        $diskName = self::diskName();
        $config = config('filesystems.disks.media_'.$diskName, []);

        if (in_array($diskName, ['ftp', 'sftp'], true) && empty($config['url'])) {
            throw new RuntimeException('A public URL must be configured for the selected remote media disk.');
        }

        if (in_array($diskName, ['upyun', 'qiniu'], true) && empty($config['domain'])) {
            throw new RuntimeException('A public domain must be configured for the selected media provider.');
        }

        if ($diskName === 'aliyun' && (empty($config['bucket']) || empty($config['endpoint']))) {
            throw new RuntimeException('ALIYUN_OSS_BUCKET and ALIYUN_OSS_ENDPOINT must be configured before uploading.');
        }

        return Storage::disk('media_'.$diskName);
    }

    public static function url(string $diskName, Filesystem $disk, string $path): string
    {
        if ($diskName === 'local') {
            return '/storage/'.$path;
        }

        if ($diskName === 'aliyun') {
            $baseUrl = (string) config('filesystems.disks.media_aliyun.url', '');
            if ($baseUrl === '') {
                $bucket = (string) config('filesystems.disks.media_aliyun.bucket', '');
                $endpoint = (string) config('filesystems.disks.media_aliyun.endpoint', '');
                $host = parse_url(str_contains($endpoint, '://') ? $endpoint : 'https://'.$endpoint, PHP_URL_HOST);
                if ($bucket === '' || ! is_string($host) || $host === '') {
                    throw new RuntimeException('Configure ALIYUN_OSS_BUCKET and ALIYUN_OSS_ENDPOINT for public media access.');
                }
                $baseUrl = 'https://'.$bucket.'.'.$host;
            }

            return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
        }

        return $disk->url($path);
    }
}
