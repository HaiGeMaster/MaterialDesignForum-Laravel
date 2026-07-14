<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Services;

use Illuminate\Support\Carbon;
use App\Models\Option;

class Share
{
    /**
     * 动态生成 PWA webmanifest.json
     * @return array
     */
    public static function GetWebManifest(): array
    {
        $siteName  = Option::Get('site_name') ?? config('app.name', 'MaterialDesignForum');
        $shortName = Option::Get('site_short_name') ?? $siteName;
        $theme     = Option::Get('theme') ?? 'MaterialDesignForum-MDUI2';

        $primaryColor = '#2196F3';
        $bgColor      = '#FFFFFF';

        $settingFile = public_path("themes/{$theme}/setting.json");
        if (file_exists($settingFile)) {
            $setting    = json_decode(file_get_contents($settingFile), true);
            $themeColor = $setting['theme_color'] ?? $setting['default_theme_color'] ?? [];
            $light      = $themeColor['light']['colors'] ?? [];
            $dark       = $themeColor['dark']['colors'] ?? [];
            $primaryColor = $light['primary'] ?? $dark['primary'] ?? '#2196F3';
            $bgColor      = $light['background'] ?? '#FFFFFF';
        }

        return [
            'name'             => $siteName,
            'short_name'       => $shortName,
            'start_url'        => '/',
            'display'          => 'standalone',
            'background_color' => $bgColor,
            'theme_color'      => $primaryColor,
            'description'      => Option::Get('site_description') ?? '',
            'icons'            => [
                ['src' => '/favicon.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/favicon.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ];
    }

    /**
     * 获取路由主题的index.html
     * @return string
     */
    public static function GetRouteThemeIndex()
    {
        try {
            //获取数据库中的theme的value
            $theme = Option::where('name', 'theme')->first();
            $themename = 'MaterialDesignForum-Vuetify4';
            // 如果数据库里有值，就用数据库的
            if ($theme && !empty($theme->value)) {
                $themename = $theme->value;
            }
            $html = file_get_contents(public_path('themes/' . $themename . '/index.html'));
            $html = str_replace('{lang}', Option::Get('default_language') ?? app()->getLocale(), $html);
            $html = str_replace('{title}', Option::Get('site_name') ?? config('app.name'), $html);
            $html = str_replace('{keywords}', Option::Get('site_keywords') ?? '', $html);
            $html = str_replace('{description}', Option::Get('site_description') ?? '', $html);

            // 注入 Service Worker 注册脚本（PWA 安装前提）
            $swScript = <<<'SW'
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').then(reg => {
      console.log('[PWA] ServiceWorker registered:', reg.scope);
    }).catch(err => {
      console.log('[PWA] ServiceWorker failed:', err);
    });
  });

  // 拦截安装提示事件（可用于自定义安装按钮）
  let deferredPrompt;
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    // 将 deferredPrompt 暴露到 window，方便前端调用 prompt() 弹出安装
    window.__pwaInstall = () => {
      deferredPrompt.prompt();
      deferredPrompt.userChoice.then(r => {
        console.log('[PWA] User choice:', r.outcome);
        deferredPrompt = null;
      });
    };
  });
}
</script>
SW;
            $html = str_replace('</body>', $swScript . "\n</body>", $html);

            return $html;
        } catch (\Exception $e) {
            // 主题文件不存在或读取失败，返回默认提示
            return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Error</title></head><body><h1>Theme not found :(' . $e->getMessage() . '</h1></body></html>';
        }
    }
    /**
     * 处理Array数据为SQL 处理排序
     * @param string $data 数据 示例:-follower_count +follower_count
     * @return array [field,sort]
     */
    public static function HandleArrayField($data): array
    {
        //示例:-follower_count
        //获取第一个字符为排序方式
        $sort = substr($data, 0, 1);
        //获取排序字段
        $field = substr($data, 1);
        return array(
            'field' => $field,
            'sort' => $sort == '-' ? 'desc' : 'asc'
        );
    }
    /**
     * 处理Array数据为JSON
     * @param array $data 数据
     * @return string|null
     * @throws \Exception 如果JSON编码失败则抛出异常
     */
    public static function HandleArrayToJSON($data): string|null
    {
        try {
            //return json_encode($data);
            if (config('app.debug')) {
                return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                return json_encode($data); //, JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }
    /**
     * 处理JSON数据为数组
     * @param string $data 数据
     * @param bool $assoc 是否关联关联数组
     * @throws \Exception 如果JSON解码失败则抛出异常
     * @return array
     */
    public static function HandleJSONToArray($data, $assoc = false): array
    {
        if (config('app.debug') || $assoc) {
            return json_decode($data, true);
        } else {
            return json_decode($data);
        }
    }
    /**
     * 处理数据和分页
     * @param object $data 查询的分页数据数据
     * @return array ['is_get' => bool, 'data' => array, 'pagination' => array]
     */
    public static function HandleDataAndPagination($data = null)
    {
        $rdata = [
            'is_get' => false,
            'data' => null,
            'pagination' => [
                'page' => 1, //当前页码
                'per_page' => 0, //每页显示的数量
                'total' => 0, //总共有多少个项目
                'pages' => 0, //总共有多少页
                'previous' => null, //上一页
                'next' => null, //下一页
            ]
        ];
        if ($data != null) {
            // 参考旧后端：从分页 URL 中提取页码，比手工 currentPage()+1 更可靠
            $previousPageUrl = $data->total() == 1 ? null : $data->previousPageUrl();
            $nextPageUrl     = $data->total() == 1 ? null : $data->nextPageUrl(); //得出值：https://laravelmdf.xbedrock.com/api/users/get?page=2
            $previousPage    = $previousPageUrl != null ? self::getPageFromUrl($previousPageUrl) : null;
            $nextPage        = $nextPageUrl != null     ? self::getPageFromUrl($nextPageUrl)     : null;

            $data_items = $data->items();
            //如果$data_items是空数组，则返回null
            if (count($data_items) == 0 || $data_items == null || $data_items == []) {
                $data_items = null;
            }
            $rdata = [
                'is_get' => $data_items != null,
                'data' => $data_items,
                'pagination' => [
                    'page'     => $data->total() == 1 ? 1 : $data->currentPage(),
                    'per_page' => $data->total() == 1 ? 1 : $data->perPage(),
                    'total'    => $data->total(),
                    'pages'    => $data->total() == 1 ? 1 : $data->lastPage(),
                    'previous' => $previousPage,
                    'next'     => $nextPage,
                ]
            ];
        }
        return $rdata;
    }
    /**
     * 处理合并数据和分页 适用于不同表的数据和分页
     * @param array $data 查询的数据->items()
     * @param object $pagination 分页数据
     * @return array ['is_get' => bool, 'data' => array, 'pagination' => array]
     */
    public static function HandleMergeDataAndPagination($data, $pagination)
    {
        $rdata = [
            'is_get' => false,
            'data' => null,
            'pagination' => [
                'page' => 1,
                'per_page' => 0,
                'total' => 0,
                'pages' => 0,
                'previous' => null,
                'next' => null,
            ]
        ];
        if ($data != null) {
            // 参考旧后端：从分页 URL 中提取页码
            $previousPageUrl = $pagination->total() == 1 ? null : $pagination->previousPageUrl();
            $nextPageUrl     = $pagination->total() == 1 ? null : $pagination->nextPageUrl();
            $previousPage    = $previousPageUrl != null ? self::getPageFromUrl($previousPageUrl) : null;
            $nextPage        = $nextPageUrl != null     ? self::getPageFromUrl($nextPageUrl)     : null;

            $data_items = $data;
            //如果$data_items是空数组，则返回null
            if (count($data_items) == 0 || $data_items == null || $data_items == []) {
                $data_items = null;
            }
            // 额外校验：$pagination 来自"关注"表的分页，实际数据是话题/文章等不同表
            // 当实际数据不足 perPage 时（如被删除），即使分页器说还有下一页，实际也没有数据了
            $dataCount = $data_items !== null ? count($data_items) : 0;
            if ($nextPage !== null && $dataCount < $pagination->perPage()) {
                $nextPage = null;
            }

            $rdata = [
                'is_get' => $data_items != null,
                'data' => $data_items,
                'pagination' => [
                    'page'     => $pagination->total() == 1 ? 1 : $pagination->currentPage(),
                    'per_page' => $pagination->total() == 1 ? 1 : $pagination->perPage(),
                    'total'    => $pagination->total(),
                    'pages'    => $pagination->total() == 1 ? 1 : $pagination->lastPage(),
                    'previous' => $previousPage,
                    'next'     => $nextPage,
                ]
            ];
        }
        return $rdata;
    }
    /**
     * 服务器时间戳 绝对权威
     */
    public static function ServerTime()
    {
        // $timestamp = Carbon::now()->timestamp;
        // return $timestamp;
        return Carbon::now();
    }
    // /**
    //  * 验证值是否有效
    //  * @param mixed $value 值
    //  * @return bool
    //  */
    // public static function IsValid($value): bool
    // {
    //     return !empty($value) && $value !== null && $value !== false && $value != '' && $value != 0 && $value != '0' && $value != 'false';
    // }
    // public static function IsValid($value): bool
    // {
    //     return !empty($value)
    //         && !in_array(strtolower(trim((string) $value)), ['false', 'null', 'undefined', '']);
    // }

    /**
     * 从分页 URL 中提取 page 参数
     * 例如 "https://xxx.com/api/users/get?page=2" → 2
     */
    private static function getPageFromUrl($url): ?int
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query === null) {
            return null;
        }
        parse_str($query, $params);
        return isset($params['page']) ? intval($params['page']) : null;
    }
}
