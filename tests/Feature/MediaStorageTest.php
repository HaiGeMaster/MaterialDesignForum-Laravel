<?php

namespace Tests\Feature;

use App\Support\MediaStorage;
use Illuminate\Contracts\Filesystem\Filesystem;
use RuntimeException;
use Tests\TestCase;

class MediaStorageTest extends TestCase
{
    public function test_local_storage_keeps_the_existing_public_url_format(): void
    {
        config(['filesystems.media_disk' => 'local']);

        $disk = MediaStorage::disk();

        $this->assertSame('/storage/uploads/image/avatar.jpg', MediaStorage::url('local', $disk, 'uploads/image/avatar.jpg'));
    }

    public function test_aliyun_url_is_built_from_bucket_and_endpoint_when_no_custom_url_is_set(): void
    {
        config([
            'filesystems.disks.media_aliyun.bucket' => 'forum-assets',
            'filesystems.disks.media_aliyun.endpoint' => 'oss-cn-hangzhou.aliyuncs.com',
            'filesystems.disks.media_aliyun.url' => '',
        ]);
        $disk = $this->createMock(Filesystem::class);

        $url = MediaStorage::url('aliyun', $disk, 'uploads/image/avatar.jpg');

        $this->assertSame('https://forum-assets.oss-cn-hangzhou.aliyuncs.com/uploads/image/avatar.jpg', $url);
    }

    public function test_ftp_storage_requires_a_public_url_before_uploading(): void
    {
        config([
            'filesystems.media_disk' => 'ftp',
            'filesystems.disks.media_ftp.url' => '',
        ]);

        $this->expectException(RuntimeException::class);
        MediaStorage::disk();
    }
}
