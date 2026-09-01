<?php

function del(string $d): void
{
    if (! file_exists($d)) {
        return;
    }
    foreach (glob($d . '/*') ?: [] as $f) {
        if (is_dir($f)) {
            del($f);
        } else {
            @unlink($f);
        }
    }
    @rmdir($d);
}

del(__DIR__ . '/_i18n_test');
del(__DIR__ . '/../storage/app/i18n-backup');
@unlink(__DIR__ . '/../lang/zh/test.php');
@unlink(__DIR__ . '/../lang/en/test.php');
echo "cleaned\n";
