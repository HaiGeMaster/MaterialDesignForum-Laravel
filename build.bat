@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

set "ROOT=%~dp0"
cd /d "%ROOT%"

:: ====== 生成时间戳 yyyyMMddHHmmss ======
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMddHHmmss"') do set "TIMESTAMP=%%i"

:: ====== 生成构建版本号 yyyy.MM.dd 并写入 composer.json ======
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy.MM.dd"') do set "BUILD_VERSION=%%i"
powershell -NoProfile -Command "$root = '%ROOT%'; $ver = '%BUILD_VERSION%'; $path = Join-Path $root 'composer.json'; $json = Get-Content $path -Raw -Encoding UTF8; $json = $json -replace '(\"version\"\s*:\s*\")[^\"]*', ('${1}' + $ver); [System.IO.File]::WriteAllText($path, $json, [System.Text.UTF8Encoding]::new($false))"
echo [Info] composer.json version updated to %BUILD_VERSION%

:: ====== 输出文件路径 ======
set "OUTPUT=%ROOT%MDF-APP-Laravel_%TIMESTAMP%.7z"

:: ====== 7z 路径检测 ======
set "SEVENZIP=D:\App\7-Zip\7z.exe"
if not exist "%SEVENZIP%" (
    echo [Error] 7z not found: %SEVENZIP%
    pause
    exit /b 1
)

:: ====== 删除旧包 ======
if exist "%OUTPUT%" (
    echo [Info] Deleting old package: %OUTPUT%
    del /q "%OUTPUT%"
)

:: ====== 生成临时文件列表 ======
set "LIST=%TEMP%\mdforum_deploy_list.txt"
if exist "%LIST%" del /q "%LIST%"

echo app\>>"%LIST%"
echo bootstrap\>>"%LIST%"
echo config\>>"%LIST%"
echo database\>>"%LIST%"
echo lang\>>"%LIST%"
echo public\>>"%LIST%"
echo resources\>>"%LIST%"
echo routes\>>"%LIST%"
echo storage\app\>>"%LIST%"
echo storage\framework\>>"%LIST%"
echo vendor\>>"%LIST%"
echo artisan>>"%LIST%"
echo composer.json>>"%LIST%"
echo composer.lock>>"%LIST%"
echo package.json>>"%LIST%"
echo pnpm-lock.yaml>>"%LIST%"
echo .env.example>>"%LIST%"
echo .editorconfig>>"%LIST%"
echo .npmrc>>"%LIST%"
echo README.md>>"%LIST%"
echo LICENSE>>"%LIST%"
echo vite.config.js>>"%LIST%"
echo phpunit.xml>>"%LIST%"
echo index.html>>"%LIST%"

echo [Info] Building deployment package...
echo [Info] Output: %OUTPUT%

:: ====== 7z 打包（关闭延迟扩展避免 ! 转义问题）======
setlocal disabledelayedexpansion

"%SEVENZIP%" a -t7z -mx5 -xr!node_modules -xr!.git -xr!.vscode -xr!.playwright-cli -xr!tests -xr!scripts -xr!dist -xr!新建文件夹 -xr!*.log -xr!.env -xr!installed.lock -xr!verify_404.ps1 -xr!public\storage -xr!storage\app\public\uploads -xr!storage\app\installed.lock -xr!storage\framework\cache\* -xr!storage\framework\views\* -xr!storage\framework\sessions\* -xr!storage\logs\* -scsUTF-8 "%OUTPUT%" @"%LIST%"

set "EXITCODE=%ERRORLEVEL%"
endlocal & set "EXITCODE=%EXITCODE%"

if exist "%LIST%" del /q "%LIST%"

if "%EXITCODE%" equ 0 (
    echo.
    echo ========================================
    echo  [Done] Package created successfully
    echo  File: %OUTPUT%
    for %%A in ("%OUTPUT%") do echo  Size: %%~zA bytes
    echo ========================================
    echo.
    echo  部署说明:
    echo  1. 解压到目标服务器
    echo  2. 复制 .env.example 为 .env 并填写配置
    echo  3. php artisan storage:link
    echo  4. php artisan migrate --force
    echo  5. 访问 /install 走安装向导（推荐）
) else (
    echo.
    echo [Failed] Packaging failed with exit code %EXITCODE%
)

endlocal
