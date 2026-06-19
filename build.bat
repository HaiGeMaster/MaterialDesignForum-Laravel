@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

set "ROOT=%~dp0"
set "OUTPUT=%ROOT%MaterialDesignForum-Laravel.7z"
set "SEVENZIP=D:\App\7-Zip\7z.exe"

:: 检查 7z 是否可用
if not exist "%SEVENZIP%" (
    echo [错误] 未找到 7z，路径: %SEVENZIP%
    pause
    exit /b 1
)

:: 删除旧的部署包
if exist "%OUTPUT%" (
    echo [信息] 删除旧包: %OUTPUT%
    del /q "%OUTPUT%"
)

echo [信息] 正在打包部署包...

:: 生成文件列表（排除不需要的）
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
echo vendor\>>"%LIST%"
echo artisan>>"%LIST%"
echo composer.json>>"%LIST%"
echo composer.lock>>"%LIST%"
echo .env.example>>"%LIST%"

:: 7z 打包（关闭延迟扩展，避免 ! 被转义）
setlocal disabledelayedexpansion
"%SEVENZIP%" a -t7z -mx5 -xr!node_modules -xr!.git -xr!*.log -xr!storage\framework\cache\* -xr!storage\framework\views\* -xr!storage\framework\sessions\* -scsUTF-8 "%OUTPUT%" "@%LIST%"
endlocal

if %ERRORLEVEL% equ 0 (
    echo.
    echo [完成] 部署包已生成: %OUTPUT%
    for %%A in ("%OUTPUT%") do echo [大小] %%~zA 字节
) else (
    echo.
    echo [失败] 打包出错，请检查
)

if exist "%LIST%" del /q "%LIST%"
endlocal
