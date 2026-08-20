<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('email.subject_notification', ['appName' => $appName]) }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f8;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
            color: #333;
        }
        .container {
            max-width: 520px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }
        .header {
            background-color: #1e88e5;
            color: #ffffff;
            text-align: center;
            padding: 28px 24px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .body {
            padding: 32px 28px;
            line-height: 1.8;
        }
        .content {
            font-size: 16px;
            padding: 16px 20px;
            background-color: #f7f9fb;
            border-left: 4px solid #1e88e5;
            border-radius: 8px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .tip {
            font-size: 13px;
            color: #888;
            margin-top: 24px;
            border-top: 1px dashed #ddd;
            padding-top: 16px;
        }
        .footer {
            text-align: center;
            padding: 18px;
            font-size: 12px;
            color: #aaa;
            background-color: #fafbfc;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('email.subject_notification', ['appName' => $appName]) }}</h1>
        </div>
        <div class="body">
            <p>{{ __('email.greeting') }}</p>

            <div class="content">{{ $content }}</div>

            <p class="tip">{{ __('email.auto_tip') }}</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ $appName }} {{ __('email.copyright') }}
        </div>
    </div>
</body>
</html>
