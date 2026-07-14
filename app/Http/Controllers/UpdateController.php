<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\UserGroupController;

class UpdateController extends Controller
{
    /**
     * 远程更新 API 地址
     */
    private static string $remoteApiUrl = 'https://www.xbedrock.com/update/server/info';

    /**
     * 默认更新下载链接
     */
    private static string $defaultDownloadUrl = 'https://www.123912.com/s/VFB9-QEnsd';

    /**
     * 客户端：检查是否有新版本
     * 请求远程主站点获取最新版本号，与本地 composer.json 对比
     *
     * @param string $user_token 用户令牌
     * @return array{is_update: bool, new_version: string|null, current_version: string|null, download_url?: string|null, error_message?: string}
     */
    public static function checkUpdate(string $user_token): array
    {
        if (!UserGroupController::IsAdmin($user_token)) {
            return self::buildResult(false, null, null, '没有权限执行更新检查。');
        }

        try {
            $certPath = storage_path('certs/cacert.pem');

            $response = Http::timeout(30)
                ->withOptions(['verify' => file_exists($certPath) ? $certPath : true])
                ->get(self::$remoteApiUrl);

            if (!$response->successful()) {
                return self::buildResult(false, null, null, '无法连接更新服务器，HTTP: ' . $response->status());
            }

            $remoteData    = $response->json();
            $remoteVersion = $remoteData['new_version'] ?? null;

            if (empty($remoteVersion)) {
                return self::buildResult(false, null, null, '远程版本信息无效。');
            }

            $localVersion = self::getLocalVersion();

            if ($localVersion === $remoteVersion) {
                return self::buildResult(false, $remoteVersion, $localVersion);
            }

            return self::buildResult(true, $remoteVersion, $localVersion, null, $remoteData['download_url'] ?? null);
        } catch (\Exception $e) {
            return self::buildResult(false, null, self::getLocalVersion(), $e->getMessage());
        }
    }

    /**
     * 主站点端：提供最新版本信息
     * 读取 base_path('composer.json') 中的 version 字段并返回
     *
     * @return array{is_get: bool, new_version: string|null, download_url?: string|null}
     */
    public static function serveUpdateInfo(): array
    {
        $localVersion = self::getLocalVersion();

        if ($localVersion === null) {
            return [
                'is_get'      => false,
                'new_version' => null,
            ];
        }

        $downloadUrl = config('app.update_download_url')??self::$defaultDownloadUrl;

        $result = [
            'is_get'      => true,
            'new_version' => $localVersion,
        ];

        if (!empty($downloadUrl)) {
            $result['download_url'] = $downloadUrl;
        }

        return $result;
    }

    /**
     * 构建统一格式的返回数组
     */
    private static function buildResult(
        bool $isUpdate,
        ?string $newVersion,
        ?string $currentVersion,
        ?string $errorMessage = null,
        ?string $downloadUrl = null
    ): array {
        $result = [
            'is_update'       => $isUpdate,
            'new_version'     => $newVersion,
            'current_version' => $currentVersion,
            'getLocalVersion()' => self::getLocalVersion(),
        ];

        if ($errorMessage !== null) {
            $result['error_message'] = $errorMessage;
        }

        if ($downloadUrl !== null) {
            $result['download_url'] = $downloadUrl;
        }

        return $result;
    }

    /**
     * 从本地 composer.json 读取版本号
     *
     * @return string|null
     */
    private static function getLocalVersion(): ?string
    {
        $composerPath = base_path('composer.json');

        if (!file_exists($composerPath)) {
            return null;
        }

        $content = file_get_contents($composerPath);
        if ($content === false) {
            return null;
        }

        // 移除 UTF-8 BOM（Windows PowerShell 的 Set-Content -Encoding UTF8 会写入 BOM）
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }

        return $data['version'] ?? null;
    }
}
