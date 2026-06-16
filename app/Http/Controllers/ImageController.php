<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Http\Controllers;

use App\Models\Image;
use App\Services\AvatarGenerator;
use App\Services\Share;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController extends Controller
{
    /**
     * 图片上传基础路径（相对于 public 目录）
     */
    private const BASE_PATH = 'static/upload';

    /**
     * 图片类型配置
     */
    public static array $pathData = [
        'user_avatar' => [
            'path'          => 'user/avatars',
            'needDeleteOld' => true,
            'sizeArray'     => [
                'original' => [512, 512],
                'small'    => [64, 64],
                'middle'   => [128, 128],
                'large'    => [256, 256],
            ],
        ],
        'user_cover' => [
            'path'          => 'user/covers',
            'needDeleteOld' => true,
            'sizeArray'     => [
                'original' => null,
                'small'    => [600, 336],
                'middle'   => [1050, 588],
                'large'    => [1450, 812],
            ],
        ],
        'user_avatar_default' => [
            'path'          => 'user/avatars/default',
            'needDeleteOld' => false,
            'sizeArray'     => [
                'original' => [512, 512],
                'small'    => [64, 64],
                'middle'   => [128, 128],
                'large'    => [256, 256],
            ],
        ],
        'topic_cover' => [
            'path'          => 'topic/covers',
            'needDeleteOld' => true,
            'sizeArray'     => [
                'original' => null,
                'small'    => [360, 202],
                'middle'   => [720, 404],
                'large'    => [1080, 606],
            ],
        ],
        'other' => [
            'path'          => 'other',
            'needDeleteOld' => true,
            'sizeArray'     => [
                'original' => null,
            ],
        ],
        'question' => [
            'path'          => 'question',
            'needDeleteOld' => true,
            'sizeArray'     => [
                'original' => null,
            ],
        ],
        'article' => [
            'path'          => 'article',
            'needDeleteOld' => true,
            'sizeArray'     => [
                'original' => null,
            ],
        ],
        'answer' => [
            'path'          => 'answer',
            'needDeleteOld' => true,
            'sizeArray'     => [
                'original' => null,
            ],
        ],
    ];

    // ============================================================
    //  公共方法（保持向后兼容的静态接口）
    // ============================================================

    /**
     * 保存上传的图片，生成多种尺寸
     *
     * @param  string     $type       图片类型
     * @param  string     $base64data Base64 编码的图片数据
     * @param  string     $userId     用户 ID
     * @return array|false            多尺寸路径数组 或 失败 false
     */
    public static function SaveUploadImage(string $type, string $base64data, string $userId = 'cache'): array|false
    {
        if (!isset(self::$pathData[$type])) {
            Log::warning("ImageController: 未知图片类型 [{$type}]");
            return false;
        }

        $config    = self::$pathData[$type];
        $sizeArray = $config['sizeArray'];
        // 磁盘相对路径：{userId}/{typePath}，如 cache/user/avatars
        $relativePath = $userId . '/' . $config['path'];

        // 解码 Base64
        $data = self::decodeBase64Image($base64data);
        if ($data === false) {
            Log::warning('ImageController: Base64 解码失败');
            return false;
        }

        // 检测 MIME 类型
        $imageInfo = getimagesizefromstring($data);
        if ($imageInfo === false) {
            Log::warning('ImageController: 无效的图片数据');
            return false;
        }

        $mime      = $imageInfo['mime'];
        $extension = self::mimeToExtension($mime);
        if ($extension === null) {
            Log::warning("ImageController: 不支持的图片格式 [{$mime}]");
            return false;
        }

        // 生成唯一文件名
        $fileName = md5(uniqid(microtime(true), true));
        $fullBaseDir = public_path(self::BASE_PATH . '/' . $relativePath);

        // 确保目录存在
        File::ensureDirectoryExists($fullBaseDir);

        // 写入临时原始文件
        $tempFile = $fullBaseDir . '/' . $fileName . '.' . $extension;
        if (File::put($tempFile, $data) === false) {
            Log::error("ImageController: 写入临时文件失败 [{$tempFile}]");
            return false;
        }

        try {
            $result = self::processImageSizes(
                $mime,
                $extension,
                $fullBaseDir,
                $fileName,
                $sizeArray,
                $relativePath
            );
        } catch (\Throwable $e) {
            Log::error("ImageController: 图片处理异常 — {$e->getMessage()}", [
                'type'   => $type,
                'userId' => $userId,
                'trace'  => $e->getTraceAsString(),
            ]);
            return false;
        } finally {
            // 清理临时原图
            if (File::exists($tempFile)) {
                File::delete($tempFile);
            }
        }

        return $result;
    }

    /**
     * 创建用户默认头像（基于用户名的 GD 文字头像）
     *
     * @param  string $name   用户名
     * @param  string $userId 用户 ID
     * @return array          ['original' => ..., 'small' => ..., ...]
     */
    public static function CreateUserDefaultAvatar(string $name, string $userId = 'cache'): array
    {
        if ($userId === 'cache') {
            $userId = 'cache_' . md5($name) . '_' . Carbon::now()->timestamp;
        }

        $config   = self::$pathData['user_avatar_default'];
        $fileName = md5(uniqid(microtime(true), true));
        $avatar   = new AvatarGenerator($name, 512);
        $result   = [];

        foreach ($config['sizeArray'] as $key => $size) {
            // 物理路径：public/static/upload/{userId}/user/avatars/default/{size}
            $physDir = public_path(
                self::BASE_PATH . '/' . $userId . '/' . $config['path'] . '/' . $key
            );

            File::ensureDirectoryExists($physDir);

            $filePath = $physDir . '/' . $fileName . '.png';

            if ($avatar->Save($filePath, $size[0])) {
                // // 返回的 URL 路径：/public/static/upload/{userId}/user/avatars/default/{size}/{file}.png
                // $result[$key] = '/' . 'public/' . self::BASE_PATH . '/' . $userId
                //     . '/' . $config['path'] . '/' . $key
                //     . '/' . $fileName . '.png';
                // 返回的 URL 路径：/static/upload/{userId}/user/avatars/default/{size}/{file}.png
                $result[$key] = '/' . self::BASE_PATH . '/' . $userId
                    . '/' . $config['path'] . '/' . $key
                    . '/' . $fileName . '.png';
            }
        }

        $avatar->Free();

        return $result;
    }

    /**
     * 获取用户默认封面路径
     */
    public static function CreateUserDefaultCover(): array
    {
        // return [
        //     'original' => '/public/static/default/user/covers/1/original/default.png',
        //     'small'    => '/public/static/default/user/covers/1/small/default.png',
        //     'middle'   => '/public/static/default/user/covers/1/middle/default.png',
        //     'large'    => '/public/static/default/user/covers/1/large/default.png',
        // ];
        return [
            'original' => '/static/default/user/covers/1/original/default.png',
            'small'    => '/static/default/user/covers/1/small/default.png',
            'middle'   => '/static/default/user/covers/1/middle/default.png',
            'large'    => '/static/default/user/covers/1/large/default.png',
        ];
    }

    /**
     * 获取图片并返回 HTTP 响应
     *
     * @param  string $path Base64 编码的图片路径
     * @param  string $size 尺寸标识（original/small/middle/large）
     */
    public static function GetUploadImage(string $path, string $size = 'original'): BinaryFileResponse
    {
        $imagePath = './.' . base64_decode($path);

        if ($size !== '0') {
            $imagePath = str_replace('{size}', $size, $imagePath);
        }

        // 将相对路径转为绝对物理路径
        $absolutePath = public_path(ltrim(str_replace('/public', '', $imagePath), '/'));

        if (!File::exists($absolutePath)) {
            abort(404, '图片不存在');
        }

        $mimeType = File::mimeType($absolutePath) ?: 'image/png';

        return response()->file($absolutePath, [
            'Content-Type'  => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * 删除上传的图片文件及空目录
     *
     * @param  array $pathArr ['original' => ..., 'small' => ..., 'middle' => ..., 'large' => ...]
     * @return bool
     */
    public static function DeleteUploadImage(array $pathArr): bool
    {
        $paths = array_filter([
            $pathArr['original'] ?? null,
            $pathArr['small']    ?? null,
            $pathArr['middle']   ?? null,
            $pathArr['large']    ?? null,
        ]);

        // 全空 → 跳过
        if (empty($paths)) {
            return true;
        }

        // 任一为网络路径 → 跳过
        foreach ($paths as $p) {
            if (Str::startsWith($p, ['http://', 'https://'])) {
                return true;
            }
        }

        // 包含 default → 保护默认资源
        foreach ($paths as $p) {
            if (Str::contains($p, 'default')) {
                return true;
            }
        }

        // 转换为本地绝对路径
        $absolutePaths = array_map(function (string $p): string {
            $relative = ltrim(str_replace('/public', '', $p), '/');
            return public_path($relative);
        }, $paths);

        // 所有文件都不存在 → 无需操作
        $anyExists = false;
        foreach ($absolutePaths as $p) {
            if (File::exists($p)) {
                $anyExists = true;
                break;
            }
        }
        if (!$anyExists) {
            return true;
        }

        try {
            // 删除文件
            File::delete(array_values(array_filter($absolutePaths, fn($p) => File::exists($p))));

            // 清理空目录
            $dirsToClean = array_unique(array_map('dirname', $absolutePaths));
            foreach ($dirsToClean as $dir) {
                self::removeEmptyDirectory($dir);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning("ImageController: 删除图片失败 — {$e->getMessage()}");
            return true;
        }
    }

    /**
     * 添加图片记录到数据库
     *
     * @param  string      $type   图片类型
     * @param  int|null    $itemId 关联项目 ID
     * @param  string|int  $userId 用户 ID
     * @param  string      $url    图片路径
     * @param  int         $width  宽度
     * @param  int         $height 高度
     * @return bool
     */
    public static function AddImageRecord(
        string $type,
        ?int $itemId,
        string|int $userId,
        string $url,
        int $width,
        int $height
    ): bool {
        try {
            return (bool) Image::create([
                'key'         => md5($url),
                'filename'    => $url,
                'width'       => $width,
                'height'      => $height,
                'create_time' => Share::ServerTime(),
                'item_type'   => $type,
                'item_id'     => $itemId,
                'user_id'     => $userId,
            ]);
        } catch (\Throwable $e) {
            Log::error("ImageController: 添加图片记录失败 — {$e->getMessage()}", [
                'type'   => $type,
                'url'    => $url,
                'userId' => $userId,
            ]);
            return false;
        }
    }

    /**
     * 根据图片 URL 更新关联的 item_id
     */
    public static function UpdateImageItemID(string $url, int $itemId): bool
    {
        return (bool) Image::where('filename', $url)
            ->update(['item_id' => $itemId]);
    }

    // ============================================================
    //  私有辅助方法
    // ============================================================

    /**
     * 解码 Base64 图片数据（自动剥离 Data URI 前缀）
     */
    private static function decodeBase64Image(string $base64data): string|false
    {
        $img  = preg_replace('#^data:image/\w+;base64,#i', '', $base64data);
        $img  = str_replace(' ', '+', $img);
        $data = base64_decode($img, true);

        return $data ?: false;
    }

    /**
     * MIME → 文件扩展名
     */
    private static function mimeToExtension(string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png'               => 'png',
            'image/gif'               => 'gif',
            default                   => null,
        };
    }

    /**
     * 遍历尺寸配置，生成各尺寸图片文件
     *
     * @param  string $mime         MIME 类型
     * @param  string $extension    文件扩展名
     * @param  string $baseDir      物理基础目录
     * @param  string $fileName     文件名（不含扩展名）
     * @param  array  $sizeArray    尺寸配置
     * @param  string $relativePath 相对路径（用于构建返回 URL）
     * @return array
     */
    private static function processImageSizes(
        string $mime,
        string $extension,
        string $baseDir,
        string $fileName,
        array $sizeArray,
        string $relativePath
    ): array {
        $result = [
            'original' => '',
            'small'    => '',
            'middle'   => '',
            'large'    => '',
        ];

        foreach ($sizeArray as $key => $size) {
            ini_set('memory_limit', '256M');

            // 从临时原图创建 GD 资源
            $srcImage = self::createGdImage($mime, $baseDir . '/' . $fileName . '.' . $extension);
            if (!$srcImage) {
                continue;
            }

            $targetDir  = $baseDir . '/' . $key;
            $targetFile = $targetDir . '/' . $fileName . '.' . $extension;

            File::ensureDirectoryExists($targetDir);

            if ($size === null) {
                // 不缩放，直接保存
                self::saveGdImage($srcImage, $mime, $targetFile);
            } else {
                // 缩放到目标尺寸
                $resized = self::resizeImage($srcImage, $mime, $size[0], $size[1]);
                if ($resized) {
                    self::saveGdImage($resized, $mime, $targetFile);
                    imagedestroy($resized);
                }
            }

            imagedestroy($srcImage);

            // // 构建返回的 URL 路径：/public/static/upload/{userId}/{typePath}/{size}/{file}.{ext}
            // $result[$key] = '/' . 'public/' . self::BASE_PATH . '/' . $relativePath
            //     . '/' . $key . '/' . $fileName . '.' . $extension;
            // 构建返回的 URL 路径：/static/upload/{userId}/{typePath}/{size}/{file}.{ext}
            $result[$key] = '/' . self::BASE_PATH . '/' . $relativePath
                . '/' . $key . '/' . $fileName . '.' . $extension;
        }

        return $result;
    }

    /**
     * 从文件创建 GD 图像，并处理透明度
     */
    private static function createGdImage(string $mime, string $filePath): ?\GdImage
    {
        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($filePath),
            'image/png'               => @imagecreatefrompng($filePath),
            'image/gif'               => @imagecreatefromgif($filePath),
            default                   => null,
        };

        if (!$image) {
            return null;
        }

        if ($mime === 'image/png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        } elseif ($mime === 'image/gif' && imagecolortransparent($image) !== -1) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image;
    }

    /**
     * 保存 GD 图片到文件
     */
    private static function saveGdImage(\GdImage $image, string $mime, string $filePath): bool
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => imagejpeg($image, $filePath),
            'image/png'               => imagepng($image, $filePath),
            'image/gif'               => imagegif($image, $filePath),
            default                   => false,
        };
    }

    /**
     * 缩放 GD 图像到指定尺寸
     */
    private static function resizeImage(\GdImage $source, string $mime, int $width, int $height): ?\GdImage
    {
        $new = imagecreatetruecolor($width, $height);
        if (!$new) {
            return null;
        }

        $needsAlpha = ($mime === 'image/png' || $mime === 'image/gif');

        if ($needsAlpha) {
            imagealphablending($new, false);
            imagesavealpha($new, true);
            $transparent = imagecolorallocatealpha($new, 0, 0, 0, 127);
            imagefill($new, 0, 0, $transparent);
        } else {
            $white = imagecolorallocate($new, 255, 255, 255);
            imagefill($new, 0, 0, $white);
        }

        imagecopyresampled(
            $new, $source,
            0, 0,
            0, 0,
            $width, $height,
            imagesx($source), imagesy($source)
        );

        return $new;
    }

    /**
     * 递归删除空目录
     */
    private static function removeEmptyDirectory(string $dir): void
    {
        if (!File::isDirectory($dir)) {
            return;
        }

        // 递归处理子目录
        foreach (File::directories($dir) as $subDir) {
            self::removeEmptyDirectory($subDir);
        }

        // 目录为空则删除
        if (count(File::allFiles($dir)) === 0 && count(File::directories($dir)) === 0) {
            File::deleteDirectory($dir);
        }
    }
}
