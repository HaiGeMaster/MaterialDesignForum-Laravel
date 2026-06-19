<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Services;

/**
 * 基于 GD 库的文字头像生成器
 * 根据用户名生成带有首字母的彩色头像（替代缺失的 MDAvatars 类）
 */
class AvatarGenerator
{
    private ?\GdImage $image = null;
    private string $name;
    private int $size;

    /**
     * 背景色板（Material Design 风格）
     */
    private static array $colors = [
        // '#F44336', '#E91E63', '#9C27B0', '#673AB7',
        // '#3F51B5', '#2196F3', '#03A9F4', '#00BCD4',
        // '#009688', '#4CAF50', '#8BC34A', '#CDDC39',
        // '#FFC107', '#FF9800', '#FF5722', '#795548',
        // '#607D8B',

        // '#FFF4F4', // Primary Red Container
        // '#FFEAF6', // Primary Pink Container
        // '#F3E8FD', // Primary Purple Container
        // '#EEE7FC', // Primary Deep Purple Container
        // '#E8ECFA', // Primary Indigo Container
        // '#E8F1FE', // Primary Blue Container
        // '#E1F5FE', // Primary Light Blue Container
        // '#E0F7FA', // Primary Cyan Container
        // '#E0F2F1', // Primary Teal Container
        // '#EDF7ED', // Primary Green Container
        // '#F4FBE7', // Primary Light Green Container
        // '#F8FDE3', // Primary Lime Container
        // '#FFF8E1', // Primary Yellow Container
        // '#FFF3E0', // Primary Orange Container
        // '#FBE9E7', // Primary Deep Orange Container
        // '#EFEBE9', // Neutral Brown Container
        // '#ECEFF1', // Neutral Blue Grey Container

        '#FFCDD2',
        '#F8BBD0',
        '#E1BEE7',
        '#D1C4E9',
        '#C5CAE9',
        '#BBDEFB',
        '#B3E5FC',
        '#B2EBF2',
        '#B2DFDB',
        '#C8E6C9',
        '#DCEDC8',
        '#F0F4C3',
        '#FFF9C4',
        '#FFE0B2',
        '#FFCCBC',
        '#D7CCC8',
        '#CFD8DC',
    ];


    /**
     * @param string $name 用户名
     * @param int    $size 头像尺寸（正方形，像素）
     */
    public function __construct(string $name, int $size = 512)
    {
        $this->name = $name;
        $this->size = max(32, min(2048, $size));
    }

    /**
     * 保存头像到指定路径（兼容原 MDAvatars::Save 接口）
     *
     * @param  string $filePath 输出文件完整路径
     * @param  int    $size     输出尺寸
     * @return bool
     */
    public function Save(string $filePath, int $size = 0): bool
    {
        $targetSize = $size > 0 ? $size : $this->size;

        $image = $this->buildImage($targetSize);

        if ($image === null) {
            return false;
        }

        // 确保目录存在
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $result = imagepng($image, $filePath);
        imagedestroy($image);

        return (bool) $result;
    }

    /**
     * 释放资源（兼容原 MDAvatars::Free 接口）
     */
    public function Free(): void
    {
        if ($this->image !== null) {
            imagedestroy($this->image);
            $this->image = null;
        }
    }

    /**
     * 构建头像 GD 图像
     */
    private function buildImage(int $size): ?\GdImage
    {
        $image = imagecreatetruecolor($size, $size);

        if ($image === false) {
            return null;
        }

        // 启用抗锯齿
        imageantialias($image, true);

        // 背景色
        $bgHex = $this->pickBackgroundColor();
        $r = hexdec(substr($bgHex, 1, 2));
        $g = hexdec(substr($bgHex, 3, 2));
        $b = hexdec(substr($bgHex, 5, 2));
        $bgColor = imagecolorallocate($image, $r, $g, $b);

        imagefill($image, 0, 0, $bgColor);

        // 文字颜色（白色）
        $textColor = imagecolorallocate($image, 255, 255, 255);

        // 绘制文字
        $text = $this->getInitials();
        // $fontSize = (int) $size / 2;

        $fontSize = (int) round($size * 0.42);

        // // PHP 8.4+ FreeType 改用 72 DPI，需补偿回 96 DPI 的渲染效果
        // if (PHP_VERSION_ID >= 80400) {
        //     $fontSize = (int) round($fontSize * 4 / 3);
        // }

        $fontPath = $this->findFont();
        if ($fontPath !== null) {
            $bbox   = imagettfbbox($fontSize, 0, $fontPath, $text);
            // 用所有顶点计算实际宽度和高度，避免单边取点偏差
            $leftX   = min($bbox[0], $bbox[6]);
            $rightX  = max($bbox[2], $bbox[4]);
            $topY    = min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
            $bottomY = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
            $width   = $rightX - $leftX;
            $height  = $bottomY - $topY;
            $x       = (int) round(($size - $width) / 2 - $leftX);
            $y       = (int) round(($size - $height) / 2 - $topY);
            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $text);
        } else {
            // 无可用 TTF 字体时回退到内置字体
            $builtinFont = 5;
            $charWidth   = imagefontwidth($builtinFont);
            $charHeight  = imagefontheight($builtinFont);
            $x           = (int) round(($size - $charWidth * mb_strlen($text)) / 2);
            $y           = (int) round(($size - $charHeight) / 2);
            imagestring($image, $builtinFont, $x, $y, $text, $textColor);
        }

        $this->image = $image;

        return $image;
    }

    /**
     * 提取用户名首字母（最多 2 个字符）
     */
    private function getInitials(): string
    {
        $name = trim($this->name);

        if (empty($name)) {
            return '?';
        }

        // 英文用户名：取首字母
        if (preg_match('/^[a-zA-Z]/', $name)) {
            $parts = explode(' ', $name);
            if (count($parts) >= 2) {
                return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
            }
            return strtoupper(mb_substr($name, 0, 2));
        }

        // 中文用户名：取第一个字
        return mb_substr($name, 0, 1);
    }

    /**
     * 根据用户名哈希选取一致的颜色
     */
    private function pickBackgroundColor(): string
    {
        $index = abs(crc32($this->name)) % count(self::$colors);
        return self::$colors[$index];
    }

    /**
     * 查找操作系统可用的 TrueType 中文字体
     */
    private function findFont(): ?string
    {
        $candidates = [
            // Windows
            'C:\Windows\Fonts\msyh.ttc',
            'C:\Windows\Fonts\msyhbd.ttc',
            'C:\Windows\Fonts\simhei.ttf',
            'C:\Windows\Fonts\simsun.ttc',
            'C:\Windows\Fonts\arial.ttf',
            'C:\Windows\Fonts\segoeui.ttf',
            // Linux
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.ttc',
            // macOS
            '/System/Library/Fonts/PingFang.ttc',
            '/System/Library/Fonts/Helvetica.ttc',
        ];

        foreach ($candidates as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }

        return null;
    }
}
