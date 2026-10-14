@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

set "ROOT=%~dp0"

:: Generate timestamp yyyyMMddHHmmss via PowerShell
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMddHHmmss"') do set "TIMESTAMP=%%i"

:: Generate build version yyyy.MM.dd and update composer.json
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy.MM.dd"') do set "BUILD_VERSION=%%i"
powershell -NoProfile -Command ^
  "$root = '%ROOT%';" ^
  "$ver = '%BUILD_VERSION%';" ^
  "$path = Join-Path $root 'composer.json';" ^
  "$json = Get-Content $path -Raw -Encoding UTF8;" ^
  "$json = $json -replace '(""version"":\s*"")[^""]*', ('${1}' + $ver);" ^
  "[System.IO.File]::WriteAllText($path, $json, [System.Text.UTF8Encoding]::new(`$false))"
echo [Info] composer.json version updated to %BUILD_VERSION%

set "OUTPUT=%ROOT%MDForum-Laravel_%TIMESTAMP%.7z"
set "SEVENZIP=D:\App\7-Zip\7z.exe"

:: Check if 7z exists
if not exist "%SEVENZIP%" (
    echo [Error] 7z not found: %SEVENZIP%
    pause
    exit /b 1
)

:: Delete old package if exists
if exist "%OUTPUT%" (
    echo [Info] Deleting old package: %OUTPUT%
    del /q "%OUTPUT%"
)

echo [Info] Building deployment package...

:: Generate file list
set "LIST=%TEMP%\deploy_list.txt"
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
echo storage\certs\>>"%LIST%"
echo storage\framework\>>"%LIST%"
@REM echo vendor\>>"%LIST%"
echo artisan>>"%LIST%"
echo composer.json>>"%LIST%"
echo composer.lock>>"%LIST%"
echo .env.example>>"%LIST%"
echo README.md>>"%LIST%"
echo README_EN.md>>"%LIST%"
echo UPDATE.md>>"%LIST%"
echo index.html>>"%LIST%"

@REM  -xr!public\build

:: 7z pack (disable delayed expansion to avoid ! escaping)
setlocal disabledelayedexpansion
"%SEVENZIP%" a -t7z -mx5 -xr!node_modules -xr!.git -xr!*.log -xr!public\uploads -xr!public\hot -xr!public\storage -xr!resources\js -xr!resources\css -xr!public\static\upload -xr!public\storage -xr!storage\app\installed.lock -xr!storage\app\public\uploads -xr!public\themes\MaterialDesignForum-MCUI -xr!public\assets\info_content -xr!storage\framework\cache\* -xr!storage\framework\views\* -xr!storage\framework\sessions\* -scsUTF-8 "%OUTPUT%" "@%LIST%"
endlocal

if %ERRORLEVEL% equ 0 (
    echo.
    echo [Done] Package created: %OUTPUT%
    for %%A in ("%OUTPUT%") do echo [Size] %%~zA bytes
) else (
    echo.
    echo [Failed] An error occurred during packaging
)

if exist "%LIST%" del /q "%LIST%"
endlocal
