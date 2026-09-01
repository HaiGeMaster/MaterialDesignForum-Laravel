<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <title>@php
            try {
                $siteName = \Illuminate\Support\Facades\DB::table('settings')->where('name', 'site_name')->value('value');
            } catch (\Throwable $e) {
                $siteName = null;
            }
            echo e($siteName ?: config('app.name'));
        @endphp</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
