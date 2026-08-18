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
     * - 本地/无效 IP、查询失败时返回 null
     * - 返回结果由 国家 + 省份 + 城市 拼接，空值自动省略
     */
    public static function location(?string $ip): ?string
    {
        $ip = trim((string) $ip);

        if ($ip === '' || in_array($ip, ['127.0.0.1', '0.0.0.0', '::1'], true)) {
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
        ], fn ($v) => $v !== null && $v !== '');

        $location = implode('', $parts);

        return $location !== '' ? $location : null;
    }
}
