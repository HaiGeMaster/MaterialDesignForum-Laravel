$ErrorActionPreference = 'Stop'
$p = Start-Process php -ArgumentList '-S','127.0.0.1:8000','-t','public' -WorkingDirectory 'd:\Administrator\Documents\Vue\MDForumLaravel' -PassThru -WindowStyle Hidden
Start-Sleep -Seconds 3
try {
    $urls = @('/', '/install', '/install/', '/api/install/status', '/api/install', '/index.php/install')
    foreach ($u in $urls) {
        Write-Host ("GET $u -> " + (curl.exe -s -o NUL -w "code=%{http_code} redirect=%{redirect_url}" "http://127.0.0.1:8000$u"))
    }
    # 页面 / 与 /install 的 body 长度
    Write-Host ("GET / body len -> " + (curl.exe -s -w " | code=%{http_code}" "http://127.0.0.1:8000/" | Measure-Object -Character).Characters)
    Write-Host ("GET /install body -> " + (curl.exe -s -w " | code=%{http_code}" "http://127.0.0.1:8000/install" | Select-Object -First 1))
} finally {
    Stop-Process -Id $p.Id -Force
}
