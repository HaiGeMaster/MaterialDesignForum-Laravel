<?php

$backupRoot = __DIR__ . '/../storage/app/i18n-backup/20260901_070913';
$projectRoot = __DIR__ . '/..';

$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($backupRoot, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$restored = 0;
foreach ($it as $f) {
    if ($f->isFile()) {
        $rel = substr($f->getPathname(), strlen($backupRoot) + 1);
        $target = $projectRoot . DIRECTORY_SEPARATOR . $rel;
        @mkdir(dirname($target), 0755, true);
        copy($f->getPathname(), $target);
        $restored++;
        echo "恢复: " . str_replace('\\', '/', $rel) . "\n";
    }
}
echo "共恢复 {$restored} 个文件。\n";
