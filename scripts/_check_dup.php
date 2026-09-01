<?php

$lang = include __DIR__ . '/../lang/zh/messages.php';

$byValue = [];
foreach ($lang as $k => $v) {
    $byValue[$v][] = $k;
}

$dups = array_filter($byValue, fn ($keys) => count($keys) > 1);
echo '总条目: ' . count($lang) . "\n";
if ($dups) {
    echo "发现重复值 " . count($dups) . " 个:\n";
    foreach ($dups as $v => $keys) {
        echo "  " . json_encode($v, JSON_UNESCAPED_UNICODE) . " => " . implode(', ', $keys) . "\n";
    }
    exit(1);
}
echo "无重复值。\n";
