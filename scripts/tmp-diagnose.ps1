Write-Host "=== Services ==="
Get-CimInstance Win32_Service | Where-Object { $_.Name -match 'nginx|apache|httpd' } | Select-Object Name, State, PathName | Format-List

Write-Host "=== Processes on :85 ==="
$conns = Get-NetTCPConnection -LocalPort 85 -State Listen -ErrorAction SilentlyContinue
foreach ($c in $conns) {
    $p = Get-Process -Id $c.OwningProcess -ErrorAction SilentlyContinue
    Write-Host ("PID {0} -> {1}" -f $c.OwningProcess, $p.ProcessName)
    if ($p) { Write-Host ("Path: " + $p.Path) }
}

Write-Host "=== Search phpstudy dirs ==="
$drives = Get-PSDrive -PSProvider FileSystem | Select-Object -ExpandProperty Root
foreach ($d in $drives) {
    Get-ChildItem -Path $d -Directory -ErrorAction SilentlyContinue | Where-Object { $_.Name -match 'phpstudy|phpStudy' } | ForEach-Object { Write-Host $_.FullName }
}
