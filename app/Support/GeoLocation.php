<?php

namespace App\Support;

use itbdw\Ip\IpLocation;

/**
 * IP 归属地查询（基于 itbdw/ip-database 离线库，支持 IPv4 / IPv6）
 */
class GeoLocation
{
    /**
     * 根据 IP 返回归属地字符串（如「中国广东深圳市」）。
     *
     * - 本地回环地址返回「本地」
     * - 无效 IP、查询失败时返回 null
     * - 返回结果由 国家 + 省份 + 城市 拼接，空值自动省略
     */
    public static function location(?string $ip): ?string
    {
        $ip = trim((string) $ip);

        if (in_array($ip, ['127.0.0.1', '0.0.0.0', '::1'], true)) {
            return 'Local';
        }

        if ($ip === '') {
            return null;
        }

        try {
            $result = IpLocation::getLocation($ip);
        } catch (\Throwable $e) {
            return null;
        }

        if (! is_array($result) || isset($result['error'])) {
            return null;
        }

        $parts = array_filter([
            $result['country'] ?? '',
            $result['province'] ?? '',
            $result['city'] ?? '',
        ], fn($v) => $v !== null && $v !== '');

        $location = implode('', $parts);

        return $location !== '' ? $location : null;
    }
    /**
     * 获取客户端IP地址
     */
    public static function getClientIP(): string
    {
        $candidates = [];

        // XFF 是逗号分隔链，最左边是原始客户端
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $candidates[] = $ip;
                    break; // 只取第一个合法 IP
                }
            }
        }

        if (!$candidates && !empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = trim($_SERVER['HTTP_CLIENT_IP']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $candidates[] = $ip;
            }
        }

        // 兜底：REMOTE_ADDR 是 TCP 连接的对端地址，无法伪造，永远可信
        $ip = $candidates[0] ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
    // public static function getClientIP()
    // {
    //     $client_ip = '0.0.0.0';
    //     if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    //         $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    //     } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    //         $client_ip = $_SERVER['HTTP_CLIENT_IP'];
    //     } else {
    //         $client_ip = $_SERVER['REMOTE_ADDR'];
    //     }
    //     return $client_ip;
    // }
}
