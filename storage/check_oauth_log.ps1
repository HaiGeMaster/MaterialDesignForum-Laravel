$log = 'd:\Administrator\Documents\Vue\MDForumLaravel\storage\logs\laravel.log'
$lines = Get-Content $log
# 找出所有 ERROR 级别日志行（排除刚才的测试）
$errs = $lines | Select-String -Pattern '^\[\d{4}-\d{2}-\d{2}.*\.ERROR:' | Where-Object { $_.Line -notmatch 'OAuth log test' }
"error entries: $($errs.Count)"
$errs | Select-Object -Last 10 | ForEach-Object {
    $t = $_.Line
    if ($t.Length -gt 400) { $t = $t.Substring(0, 400) }
    "{0}: {1}" -f $_.LineNumber, $t
}
