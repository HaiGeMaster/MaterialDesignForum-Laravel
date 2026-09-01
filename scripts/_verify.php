<?php

$dirs = ['app', 'routes'];
$errors = [];
$count = 0;

foreach ($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (! $f->isFile() || $f->getExtension() !== 'php') {
            continue;
        }
        $count++;
        $out = shell_exec('php -l ' . escapeshellarg($f->getPathname()) . ' 2>&1');
        if ($out === null || str_contains($out, 'No syntax errors') === false) {
            $errors[] = trim($out);
        }
    }
}

echo "检查文件数: {$count}\n";
if ($errors) {
    echo "发现语法错误 " . count($errors) . " 个:\n";
    foreach ($errors as $e) {
        echo "  " . $e . "\n";
    }
    exit(1);
}
echo "全部通过语法检查。\n";
