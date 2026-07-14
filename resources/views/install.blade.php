<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>安装向导 - MaterialDesignForum</title>
    <link rel="icon" href="/favicon.png">
    @vite(['resources/js/installer.js'])
</head>
<body>
    <div id="installer"></div>

    <script>
        window.__INSTALLER_VERSION__ = '{{ $version }}';
        window.__INSTALLER_LOCALE__ = '{{ $locale }}';
        window.__PHP_VERSION__ = '{{ $phpVersion }}';
        window.__ENV_CHECK__ = @json($envCheck);
    </script>
</body>
</html>
