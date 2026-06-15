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

class Share
{

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
            $previousPageUrl = $data->total() == 1 ? null : $data->previousPageUrl();
            $nextPageUrl = $data->total() == 1 ? null : $data->nextPageUrl();
            $previousPageUrl = $previousPageUrl != null ? intval(str_replace('/?page=', '', $previousPageUrl)) : null;
            $nextPageUrl = $nextPageUrl != null ? intval(str_replace('/?page=', '', $nextPageUrl)) : null;
            $data_items = $data->items();
            //如果$data_items是空数组，则返回null
            if (count($data_items) == 0 || $data_items == null || $data_items == []) {
                $data_items = null;
            }
            $rdata = [
                'is_get' => $data_items != null,
                'data' => $data_items, //bug 可能为[]空数组
                'pagination' => [
                    'page' => $data->total() == 1 ? 1 : $data->currentPage(), //当前页码
                    'per_page' => $data->total() == 1 ? 1 : $data->perPage(), //每页显示的数量
                    'total' => $data->total(), //总共有多少个项目
                    'pages' => $data->total() == 1 ? 1 : $data->lastPage(), //总共有多少页
                    'previous' => $previousPageUrl, //上一页
                    'next' => $nextPageUrl, //下一页
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
                'page' => 1, //当前页码
                'per_page' => 0, //每页显示的数量
                'total' => 0, //总共有多少个项目
                'pages' => 0, //总共有多少页
                'previous' => null, //上一页
                'next' => null, //下一页
            ]
        ];
        if ($data != null) {
            $previousPageUrl = $pagination->total() == 1 ? null : $pagination->previousPageUrl();
            $nextPageUrl = $pagination->total() == 1 ? null : $pagination->nextPageUrl();
            $previousPageUrl = $previousPageUrl != null ? intval(str_replace('/?page=', '', $previousPageUrl)) : null;
            $nextPageUrl = $nextPageUrl != null ? intval(str_replace('/?page=', '', $nextPageUrl)) : null;
            $data_items = $data;
            //如果$data_items是空数组，则返回null
            if (count($data_items) == 0 || $data_items == null || $data_items == []) {
                $data_items = null;
            }
            $rdata = [
                'is_get' => $data_items != null,
                'data' => $data_items,
                'pagination' => [
                    'page' => $pagination->total() == 1 ? 1 : $pagination->currentPage(), //当前页码
                    'per_page' => $pagination->total() == 1 ? 1 : $pagination->perPage(), //每页显示的数量
                    'total' => $pagination->total(), //总共有多少个项目
                    'pages' => $pagination->total() == 1 ? 1 : $pagination->lastPage(), //总共有多少页
                    'previous' => $previousPageUrl, //上一页
                    'next' => $nextPageUrl, //下一页
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
        $timestamp = Carbon::now()->timestamp;
        return $timestamp;
    }
}
