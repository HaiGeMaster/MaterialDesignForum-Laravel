Write-Host "=== nginx cmdline ==="
Get-CimInstance Win32_Process | Where-Object { $_.Name -match 'nginx' } | Select-Object ProcessId, ExecutablePath, CommandLine | Format-List

Write-Host "=== listen sockets cmdline ==="
Get-CimInstance Win32_Process | Where-Object { $_.ProcessId -in @(26748,6208,12776,1928) } | Select-Object ProcessId, Name, ExecutablePath, CommandLine | Format-List
