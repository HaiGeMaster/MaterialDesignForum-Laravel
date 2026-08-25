<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOptionController extends Controller
{
    private const THEME_DEFAULTS = [
        'theme' => 'light',
        'default_theme' => 'light',
        'available_themes' => ['light', 'dark'],
        'custom_themes' => [],
        'colors' => [
            'light' => ['primary' => '#1867C0', 'secondary' => '#48A9A6', 'accent' => '#FB8C00'],
            'dark' => ['primary' => '#2196F3', 'secondary' => '#54B6B2', 'accent' => '#FB8C00'],
        ],
    ];

    public function theme()
    {
        return response()->json($this->themeData());
    }

    public function updateTheme(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['nullable', 'string', 'max:50'],
            'default_theme' => ['nullable', 'string', 'max:50'],
            'available_themes' => ['nullable', 'array'],
            'available_themes.*' => ['string', 'max:50'],
            'custom_themes' => ['nullable', 'array'],
            'custom_themes.*.name' => ['required', 'string', 'max:50'],
            'custom_themes.*.dark' => ['required', 'boolean'],
            'custom_themes.*.colors' => ['required', 'array'],
            'custom_themes.*.colors.primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'custom_themes.*.colors.secondary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'custom_themes.*.colors.accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colors' => ['nullable', 'array'],
            'colors.light' => ['nullable', 'array'],
            'colors.dark' => ['nullable', 'array'],
            'colors.light.primary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colors.light.secondary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colors.light.accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colors.dark.primary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colors.dark.secondary' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colors.dark.accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $defaultTheme = $validated['default_theme'] ?? $validated['theme'] ?? 'light';
        $availableThemes = $validated['available_themes'] ?? ['light', 'dark'];
        $customThemes = $validated['custom_themes'] ?? [];

        DB::table('option')->updateOrInsert(['name' => 'theme'], ['value' => $defaultTheme]);
        DB::table('option')->updateOrInsert(['name' => 'default_theme'], ['value' => $defaultTheme]);
        DB::table('option')->updateOrInsert(['name' => 'available_themes'], ['value' => implode(',', $availableThemes)]);
        DB::table('option')->updateOrInsert(['name' => 'custom_themes'], ['value' => json_encode($customThemes, JSON_UNESCAPED_SLASHES)]);

        // 兼容旧版 theme_color 存储
        if (isset($validated['colors'])) {
            DB::table('option')->updateOrInsert([
                'name' => 'theme_color',
            ], [
                'value' => json_encode($validated['colors'], JSON_UNESCAPED_SLASHES),
            ]);
        }

        return response()->json(['is_set' => true] + $this->themeData());
    }

    private const SITE_FIELDS = [
        'site_name',
        'site_description',
        'site_keywords',
        'site_icp_beian',
        'site_gongan_beian',
        'default_language',
    ];

    private const MAIL_FIELDS = [
        'MAIL_MAILER',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_SCHEME',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
    ];

    private const OAUTH_PROVIDERS = ['github', 'google', 'microsoft'];

    private const OAUTH_SECRETS = [
        'GITHUB_CLIENT_SECRET',
        'GOOGLE_CLIENT_SECRET',
        'MICROSOFT_CLIENT_SECRET',
    ];

    public function mail()
    {
        return response()->json(['data' => $this->mailData()]);
    }

    public function updateMail(Request $request)
    {
        $validated = $request->validate([
            'MAIL_MAILER' => ['required', 'string', 'max:30'],
            'MAIL_HOST' => ['required', 'string', 'max:255'],
            'MAIL_PORT' => ['required', 'integer', 'between:1,65535'],
            'MAIL_SCHEME' => ['nullable', 'in:,smtps,smtp'],
            'MAIL_USERNAME' => ['nullable', 'string', 'max:255'],
            'MAIL_PASSWORD' => ['nullable', 'string', 'max:255'],
            'MAIL_FROM_ADDRESS' => ['required', 'email', 'max:255'],
            'MAIL_FROM_NAME' => ['required', 'string', 'max:255'],
        ]);

        $scheme = $validated['MAIL_SCHEME'] ?? '';
        $values = [
            'MAIL_MAILER' => $validated['MAIL_MAILER'],
            'MAIL_HOST' => $validated['MAIL_HOST'],
            'MAIL_PORT' => (string) $validated['MAIL_PORT'],
            'MAIL_SCHEME' => $scheme,
            'MAIL_USERNAME' => $validated['MAIL_USERNAME'] ?? '',
            'MAIL_FROM_ADDRESS' => $validated['MAIL_FROM_ADDRESS'],
            'MAIL_FROM_NAME' => $validated['MAIL_FROM_NAME'],
        ];
        if (($validated['MAIL_PASSWORD'] ?? '') !== '') {
            $values['MAIL_PASSWORD'] = $validated['MAIL_PASSWORD'];
        }

        $this->updateEnvFile($values);
        config([
            'mail.default' => $values['MAIL_MAILER'],
            'mail.mailers.smtp.host' => $values['MAIL_HOST'],
            'mail.mailers.smtp.port' => (int) $values['MAIL_PORT'],
            'mail.mailers.smtp.scheme' => $scheme ?: null,
            'mail.mailers.smtp.username' => $values['MAIL_USERNAME'],
            'mail.mailers.smtp.password' => $values['MAIL_PASSWORD'] ?? config('mail.mailers.smtp.password'),
            'mail.from.address' => $values['MAIL_FROM_ADDRESS'],
            'mail.from.name' => $values['MAIL_FROM_NAME'],
        ]);

        return response()->json(['is_set' => true, 'data' => $this->mailData()]);
    }

    public function oauth()
    {
        return response()->json(['data' => $this->oauthData()]);
    }

    public function updateOAuth(Request $request)
    {
        $rules = [];
        foreach (self::OAUTH_PROVIDERS as $provider) {
            $upper = strtoupper($provider);
            $rules["{$upper}_CLIENT_ID"] = ['nullable', 'string', 'max:255'];
            $rules["{$upper}_CLIENT_SECRET"] = ['nullable', 'string', 'max:255'];
        }
        $validated = $request->validate($rules);

        $values = [];
        foreach (self::OAUTH_PROVIDERS as $provider) {
            $upper = strtoupper($provider);
            $idKey = "{$upper}_CLIENT_ID";
            $secretKey = "{$upper}_CLIENT_SECRET";

            // 留空的 ID 也允许清空（用户可能希望取消绑定）
            $values[$idKey] = (string) ($validated[$idKey] ?? '');

            // 仅当用户填写了非空 secret 时才覆盖；留空表示保持现有值
            if (($validated[$secretKey] ?? '') !== '') {
                $values[$secretKey] = $validated[$secretKey];
            }
        }

        $this->updateEnvFile($values);

        // 同步运行时 config（兼容 Laravel Socialite 等读取 services.* 的场景）
        foreach (self::OAUTH_PROVIDERS as $provider) {
            $upper = strtoupper($provider);
            $idKey = "{$upper}_CLIENT_ID";
            $secretKey = "{$upper}_CLIENT_SECRET";
            if (array_key_exists($idKey, $values)) {
                config(["services.{$provider}.client_id" => $values[$idKey]]);
            }
            if (array_key_exists($secretKey, $values)) {
                config(["services.{$provider}.client_secret" => $values[$secretKey]]);
            }
        }

        return response()->json(['is_set' => true, 'data' => $this->oauthData()]);
    }

    public function siteInfo()
    {
        $rows = DB::table('option')
            ->whereIn('name', self::SITE_FIELDS)
            ->pluck('value', 'name');

        $data = [];
        foreach (self::SITE_FIELDS as $field) {
            $data[$field] = (string) ($rows[$field] ?? '');
        }

        return response()->json(['data' => $data]);
    }

    private function themeData(): array
    {
        $rows = DB::table('option')->whereIn('name', [
            'theme', 'theme_color', 'default_theme', 'available_themes', 'custom_themes',
            'site_name', 'site_icp_beian', 'site_gongan_beian',
        ])->pluck('value', 'name');
        $colors = json_decode($rows['theme_color'] ?? '', true);
        $customThemes = json_decode($rows['custom_themes'] ?? '[]', true);
        $availableThemesRaw = $rows['available_themes'] ?? '';

        $defaultTheme = $rows['default_theme'] ?? $rows['theme'] ?? 'light';
        if (!in_array($defaultTheme, ['light', 'dark', 'system'], true)) {
            // 允许任意自定义主题名
            $defaultTheme = $defaultTheme;
        }

        return [
            'theme' => $defaultTheme,
            'default_theme' => $defaultTheme,
            'available_themes' => $availableThemesRaw !== '' ? array_filter(explode(',', $availableThemesRaw)) : ['light', 'dark'],
            'custom_themes' => is_array($customThemes) ? $customThemes : [],
            'colors' => is_array($colors)
                ? array_replace_recursive(self::THEME_DEFAULTS['colors'], $colors)
                : self::THEME_DEFAULTS['colors'],
            'site_name' => (string) ($rows['site_name'] ?? 'Material Design Forum'),
            'site_icp_beian' => (string) ($rows['site_icp_beian'] ?? ''),
            'site_gongan_beian' => (string) ($rows['site_gongan_beian'] ?? ''),
        ];
    }

    public function updateSiteInfo(Request $request)
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_description' => ['nullable', 'string', 'max:1000'],
            'site_keywords' => ['nullable', 'string', 'max:1000'],
            'site_icp_beian' => ['nullable', 'string', 'max:120'],
            'site_gongan_beian' => ['nullable', 'string', 'max:120'],
            'default_language' => ['required', 'string', 'max:20'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach (self::SITE_FIELDS as $field) {
                DB::table('option')->updateOrInsert(
                    ['name' => $field],
                    ['value' => $validated[$field] ?? ''],
                );
            }
        });

        return response()->json([
            'is_set' => true,
            'data' => $validated,
        ]);
    }

    private function mailData(): array
    {
        $scheme = (string) config('mail.mailers.smtp.scheme', '');
        $password = (string) config('mail.mailers.smtp.password', '');
        return [
            'MAIL_MAILER' => (string) config('mail.default', 'smtp'),
            'MAIL_HOST' => (string) config('mail.mailers.smtp.host', ''),
            'MAIL_PORT' => (string) config('mail.mailers.smtp.port', '2525'),
            'MAIL_SCHEME' => $scheme,
            'MAIL_USERNAME' => (string) config('mail.mailers.smtp.username', ''),
            'MAIL_PASSWORD' => '',
            'password_set' => $password !== '',
            'MAIL_FROM_ADDRESS' => (string) config('mail.from.address', ''),
            'MAIL_FROM_NAME' => (string) config('mail.from.name', ''),
        ];
    }

    private function oauthData(): array
    {
        $data = [];
        foreach (self::OAUTH_PROVIDERS as $provider) {
            $upper = strtoupper($provider);
            $idKey = "{$upper}_CLIENT_ID";
            $secretKey = "{$upper}_CLIENT_SECRET";

            // ID 通过 env() 直接读取（设置后立即生效），不回显 secret 内容
            $data[$idKey] = (string) (env($idKey, config("services.{$provider}.client_id", '')));
            $data[$secretKey] = '';
            $data["{$upper}_SECRET_SET"] = (string) env($secretKey, config("services.{$provider}.client_secret", '')) !== '';
        }
        return $data;
    }

    private function updateEnvFile(array $values): void
    {
        $path = base_path('.env');
        $contents = file_exists($path) ? file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            $encoded = $this->encodeEnvValue((string) $value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $key . '=' . $encoded, $contents);
            } else {
                $contents .= (str_ends_with($contents, "\n") || $contents === '' ? '' : "\n") . $key . '=' . $encoded . "\n";
            }
        }

        file_put_contents($path, $contents, LOCK_EX);
    }

    private function encodeEnvValue(string $value): string
    {
        return $value !== '' && preg_match('/^[A-Za-z0-9._@:\/-]+$/', $value)
            ? $value
            : '"' . addcslashes($value, "\\\"") . '"';
    }
}
