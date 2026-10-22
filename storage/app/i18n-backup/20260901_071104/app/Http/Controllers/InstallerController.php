<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * 首次安装部署控制器
 *
 * 提供多步骤安装向导的 API：
 *   GET  /api/install/requirements  环境检查
 *   POST /api/install/database       测试并保存数据库配置
 *   POST /api/install/app            保存应用配置（时区、邮件）
 *   POST /api/install/run            执行迁移 + seed + 创建第一个管理员
 *
 * 已安装后所有写操作均被拒绝（通过 installed.lock 锁文件判断）。
 */
class InstallerController extends Controller
{
    /** 安装锁文件（相对 storage_path） */
    private const LOCK_FILE = 'app/installed.lock';

    /** .env 文件写入时允许的键白名单 */
    private const ENV_KEYS = [
        'APP_ENV', 'APP_NAME', 'APP_DEBUG', 'APP_URL',
        'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD',
        'APP_TIMEZONE', 'APP_LOCALE',
        'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD',
        'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME', 'MAIL_SCHEME',
    ];

    public function __construct()
    {
        // 锁文件存在则禁止继续安装（路由层防御）
        // status() 方法例外：它本身就是为了查询安装状态，必须对未安装/已安装两种情况都返回
        $action = request()->route()?->getActionMethod();
        if ($action !== 'status' && file_exists($this->lockPath())) {
            abort(403, '系统已安装，禁止再次执行安装程序。');
        }
    }

    /**
     * 安装状态查询（公开接口，不受锁文件限制）
     *
     * 前端首次加载时调用，未安装则跳转到 /install 路由。
     */
    public function status()
    {
        $lockPath = $this->lockPath();
        $installed = file_exists($lockPath);

        $info = ['installed' => $installed];

        if ($installed) {
            $data = json_decode(file_get_contents($lockPath), true) ?: [];
            $info['installed_at']  = $data['installed_at'] ?? null;
            $info['version']       = $data['version'] ?? null;
            $info['admin_user_id'] = $data['admin_user_id'] ?? null;
            $info['admin_email']   = $data['admin_email'] ?? null;
        }

        return response()->json($info);
    }

    /**
     * 环境与权限检查
     *
     * 返回结构（每项 check）：
     *   - pass:    bool       是否通过
     *   - status:  string     标准化状态码（enabled/disabled/writable/not_writable/version）
     *                          前端通过 t(`install.requirements.status.${code}`) 翻译
     *   - detail:  string     具体值（如 "PHP 8.3.0"），前端直接显示
     *   - label:   string     中文兜底标签（前端优先用 i18n 翻译 labels.<key>）
     */
    public function requirements()
    {
        $envReady = $this->ensureEnvFile();

        $checks = [
            'php_version' => [
                'label'   => 'PHP 版本 ≥ 8.2',
                'pass'     => version_compare(PHP_VERSION, '8.2.0', '>='),
                'status'  => 'version',
                'detail'  => 'PHP ' . PHP_VERSION,
            ],
            'pdo_mysql' => [
                'label'   => 'PDO MySQL 扩展',
                'pass'     => extension_loaded('pdo_mysql'),
                'status'  => extension_loaded('pdo_mysql') ? 'enabled' : 'disabled',
                'detail'  => '',
            ],
            'openssl' => [
                'label'   => 'OpenSSL 扩展',
                'pass'     => extension_loaded('openssl'),
                'status'  => extension_loaded('openssl') ? 'enabled' : 'disabled',
                'detail'  => '',
            ],
            'mbstring' => [
                'label'   => 'Mbstring 扩展',
                'pass'     => extension_loaded('mbstring'),
                'status'  => extension_loaded('mbstring') ? 'enabled' : 'disabled',
                'detail'  => '',
            ],
            'fileinfo' => [
                'label'   => 'Fileinfo 扩展',
                'pass'     => extension_loaded('fileinfo'),
                'status'  => extension_loaded('fileinfo') ? 'enabled' : 'disabled',
                'detail'  => '',
            ],
            'env_writable' => [
                'label'   => '.env 文件可写',
                'pass'     => $envReady,
                'status'  => $envReady ? 'writable' : 'not_writable',
                'detail'  => '',
            ],
            'storage_writable' => [
                'label'   => 'storage 目录可写',
                'pass'     => is_writable(storage_path()),
                'status'  => is_writable(storage_path()) ? 'writable' : 'not_writable',
                'detail'  => '',
            ],
            'bootstrap_cache_writable' => [
                'label'   => 'bootstrap/cache 目录可写',
                'pass'     => is_writable(base_path('bootstrap/cache')),
                'status'  => is_writable(base_path('bootstrap/cache')) ? 'writable' : 'not_writable',
                'detail'  => '',
            ],
        ];

        $allPass = collect($checks)->every(fn ($c) => $c['pass']);

        return response()->json([
            'all_pass' => $allPass,
            'checks'   => $checks,
        ]);
    }

    /**
     * 测试并保存数据库配置到 .env
     */
    public function database(Request $request)
    {
        $data = $request->validate([
            'host'     => ['required', 'string', 'max:255'],
            'port'     => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'max:200'],
        ]);

        // 用 PDO 直接测试连接（此时 .env 尚未写入，不能用 Laravel DB facade）
        try {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $data['host'], $data['port'], $data['database']);
            $pdo = new PDO($dsn, $data['username'], $data['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $pdo->exec("SELECT 1");
        } catch (PDOException $e) {
            return response()->json([
                'message' => '数据库连接失败：' . $e->getMessage(),
            ], 422);
        }

        $this->updateEnv([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'        => $data['host'],
            'DB_PORT'        => (string) $data['port'],
            'DB_DATABASE'    => $data['database'],
            'DB_USERNAME'    => $data['username'],
            'DB_PASSWORD'    => $data['password'] ?? '',
        ]);

        return response()->json(['message' => '数据库连接成功，配置已保存。']);
    }

    /**
     * 保存应用配置（时区 + 邮件）
     */
    public function app(Request $request)
    {
        $data = $request->validate([
            'app_name'        => ['nullable', 'string', 'max:100'],
            'app_url'         => ['nullable', 'url', 'max:255'],
            'timezone'       => ['required', 'string', 'max:50'],
            'locale'         => ['nullable', 'string', 'max:10'],
            'mail_mailer'     => ['nullable', 'string', 'in:smtp,log,sendmail'],
            'mail_host'       => ['nullable', 'string', 'max:200'],
            'mail_port'       => ['nullable', 'integer', 'between:1,65535'],
            'mail_username'   => ['nullable', 'string', 'max:200'],
            'mail_password'   => ['nullable', 'string', 'max:200'],
            'mail_from_address' => ['nullable', 'email', 'max:200'],
            'mail_from_name'   => ['nullable', 'string', 'max:100'],
            'mail_scheme'      => ['nullable', 'string', 'in:smtps,smtp,'],
        ]);

        $env = [
            'APP_NAME'    => $data['app_name'] ?? config('app.name', 'MDForum'),
            'APP_TIMEZONE' => $data['timezone'],
            'APP_LOCALE'   => $data['locale'] ?? 'zh_CN',
        ];

        // 本站域名地址（可选）：去掉末尾斜杠，保证后续拼接链接时路径正确
        if (! empty($data['app_url'])) {
            $env['APP_URL'] = rtrim($data['app_url'], '/');
        }

        if (! empty($data['mail_mailer'])) {
            $env = array_merge($env, [
                'MAIL_MAILER'      => $data['mail_mailer'],
                'MAIL_HOST'        => $data['mail_host'] ?? '',
                'MAIL_PORT'        => isset($data['mail_port']) ? (string) $data['mail_port'] : '',
                'MAIL_USERNAME'    => $data['mail_username'] ?? '',
                'MAIL_PASSWORD'    => $data['mail_password'] ?? '',
                'MAIL_FROM_ADDRESS' => $data['mail_from_address'] ?? '',
                'MAIL_FROM_NAME'   => $data['mail_from_name'] ?? ($data['app_name'] ?? 'MDForum'),
                'MAIL_SCHEME'      => $data['mail_scheme'] ?? '',
            ]);
        }

        $this->updateEnv($env);

        return response()->json(['message' => '应用配置已保存。']);
    }

    /**
     * 执行最终安装：migrate + 插入用户组默认数据 + 创建管理员 + 写锁文件
     *
     * 注意：不调用 db:seed，避免把 DatabaseSeeder 中的 demo 用户（alice/bob/carol...）
     * 与示例问题混入生产环境。开发环境仍可用 `php artisan db:seed` 单独执行。
     */
    public function run(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_]+$/'],
            'email'    => ['required', 'email', 'max:320'],
            'password' => ['required', 'string', 'min:6', 'max:64'],
        ]);

        try {
            // 1. 重新加载 .env 到运行时配置（前面 database/app 步骤已写入文件，但当前进程仍用旧值）
            $this->reloadEnv();

            // 1.1 确保 APP_KEY 存在：全新创建的 .env 没有密钥，
            //     缺密钥时 Laravel 的 cookie/session 加密会抛 MissingAppKeyException（500）
            if (empty(config('app.key'))) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            // 2. 迁移（--force 跳过生产环境确认提示）
            Artisan::call('migrate', ['--force' => true]);

            // 3. 插入用户组默认数据（管理员组 + 普通用户组），幂等 upsert
            $this->seedUserGroups();

            // 3.1 管理员账号唯一性校验：migrate 之后 user 表已存在，
            //      此处手动检查 username/email 是否已被占用（删 lock 重装但保留旧数据时会触发）
            $exists = User::query()
                ->where('username', $data['username'])
                ->orWhere('email', $data['email'])
                ->exists();
            if ($exists) {
                return response()->json([
                    'message' => '管理员账号或邮箱已存在，请更换后重试。',
                ], 422);
            }

            // 4. 创建第一个管理员（user_group_id=1）
            $user = User::create([
                'username'   => $data['username'],
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'user_group_id' => 1, // 管理员组
                'create_ip'   => $request->ip() ?: '127.0.0.1',
                'last_login_time' => now(),
                'last_login_ip' => $request->ip() ?: '127.0.0.1',
                'follower_count' => 0,
                'followee_count' => 0,
                'following_topic_count' => 0,
                'following_article_count' => 0,
                'following_question_count' => 0,
                'topic_count' => 0,
                'article_count' => 0,
                'question_count' => 0,
                'answer_count' => 0,
                'comment_count' => 0,
                'reply_count' => 0,
                'notification_unread' => 0,
                'inbox_system' => 0,
                'inbox_user_group' => 0,
                'inbox_private_message' => 0,
                'language' => config('app.locale', 'zh_CN'),
                'create_time' => now(),
                'update_time' => now(),
                'disable_time' => null,
            ]);

            // 5. 创建安装锁文件，防止再次执行
            file_put_contents($this->lockPath(), json_encode([
                'installed_at' => now()->toDateTimeString(),
                'admin_user_id' => $user->user_id,
                'admin_email' => $user->email,
                'version' => '1.0.0',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // 6. 清理配置与视图缓存，避免旧缓存影响新配置
            Artisan::call('config:clear');
            Artisan::call('route:clear');

            return response()->json([
                'message'   => '安装成功！请使用刚才的管理员账号登录。',
                'admin'     => [
                    'username' => $user->username,
                    'email'    => $user->email,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => '安装失败：' . $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * 插入两个内置用户组：1=管理员（全权限）/ 2=普通用户。
     * 与 DatabaseSeeder 中的数据保持一致，便于后续 php artisan db:seed 幂等执行。
     */
    private function seedUserGroups(): void
    {
        $now = now();
        $commonStats = [
            'user_group_user_count' => 0,
            'create_time' => $now,
            'update_time' => $now,
            'delete_time' => null,
        ];

        \Illuminate\Support\Facades\DB::table('user_group')->upsert([
            array_merge($commonStats, [
                'user_group_id' => 1,
                'user_group_name' => 'Admin',
                'user_group_description' => 'Administrator',
                'user_group_icon' => 'mdi-security',
                'user_group_icon_show' => 1,
                'user_group_color' => '#2196f3',
                'is_admin' => 1,
                'ability_normal_login' => 1,
                'ability_admin_login' => 1,
                'ability_admin_manage_user_group' => 1,
                'ability_admin_manage_user' => 1,
                'ability_admin_manage_topic' => 1,
                'ability_admin_manage_question' => 1,
                'ability_admin_manage_article' => 1,
                'ability_admin_manage_comment' => 1,
                'ability_admin_manage_answer' => 1,
                'ability_admin_manage_reply' => 1,
                'ability_admin_manage_report' => 1,
                'ability_admin_manage_option' => 1,
                'ability_create_article' => 1,
                'ability_create_question' => 1,
                'ability_create_answer' => 1,
                'ability_create_comment' => 1,
                'ability_create_reply' => 1,
                'ability_create_topic' => 1,
                'ability_edit_own_article' => 1,
                'ability_edit_own_question' => 1,
                'ability_edit_own_answer' => 1,
                'ability_edit_own_comment' => 1,
                'ability_edit_own_reply' => 1,
                'ability_edit_own_topic' => 1,
                'ability_delete_own_article' => 1,
                'ability_delete_own_question' => 1,
                'ability_delete_own_answer' => 1,
                'ability_delete_own_comment' => 1,
                'ability_delete_own_reply' => 1,
                'ability_delete_own_topic' => 1,
                'time_before_edit_article' => 0,
                'time_before_edit_question' => 0,
                'time_before_edit_answer' => 0,
                'time_before_edit_comment' => 0,
                'time_before_edit_reply' => 0,
                'time_before_edit_topic' => 0,
                'time_before_delete_article' => 0,
                'time_before_delete_question' => 0,
                'time_before_delete_answer' => 0,
                'time_before_delete_comment' => 0,
                'time_before_delete_reply' => 0,
                'time_before_delete_topic' => 0,
                'ability_edit_article_only_no_comment' => 1,
                'ability_edit_question_only_no_answer' => 1,
                'ability_edit_answer_only_no_comment' => 1,
                'ability_edit_question_only_no_comment' => 1,
                'ability_edit_comment_only_no_reply' => 1,
                'ability_edit_reply_only_no_reply' => 1,
                'ability_edit_topic_only_no_article_or_question' => 1,
                'ability_delete_article_only_no_comment' => 1,
                'ability_delete_question_only_no_answer' => 1,
                'ability_delete_answer_only_no_comment' => 1,
                'ability_delete_question_only_no_comment' => 1,
                'ability_delete_comment_only_no_reply' => 1,
                'ability_delete_reply_only_no_reply' => 1,
                'ability_delete_topic_only_no_article_or_question' => 1,
                'ability_edit_own_info' => 1,
                'ability_vote' => 1,
            ]),
            array_merge($commonStats, [
                'user_group_id' => 2,
                'user_group_name' => 'User',
                'user_group_description' => 'Standard User',
                'user_group_icon' => 'mdi-account',
                'user_group_icon_show' => 0,
                'user_group_color' => '#4CAF50',
                'is_admin' => 0,
                'ability_normal_login' => 1,
                'ability_admin_login' => 0,
                'ability_admin_manage_user_group' => 0,
                'ability_admin_manage_user' => 0,
                'ability_admin_manage_topic' => 0,
                'ability_admin_manage_question' => 0,
                'ability_admin_manage_article' => 0,
                'ability_admin_manage_comment' => 0,
                'ability_admin_manage_answer' => 0,
                'ability_admin_manage_reply' => 0,
                'ability_admin_manage_report' => 0,
                'ability_admin_manage_option' => 0,
                'ability_create_article' => 1,
                'ability_create_question' => 1,
                'ability_create_answer' => 1,
                'ability_create_comment' => 1,
                'ability_create_reply' => 1,
                'ability_create_topic' => 0,
                'ability_edit_own_article' => 1,
                'ability_edit_own_question' => 1,
                'ability_edit_own_answer' => 1,
                'ability_edit_own_comment' => 1,
                'ability_edit_own_reply' => 1,
                'ability_edit_own_topic' => 0,
                'ability_delete_own_article' => 1,
                'ability_delete_own_question' => 1,
                'ability_delete_own_answer' => 1,
                'ability_delete_own_comment' => 1,
                'ability_delete_own_reply' => 1,
                'ability_delete_own_topic' => 0,
                'time_before_edit_article' => 5,
                'time_before_edit_question' => 5,
                'time_before_edit_answer' => 5,
                'time_before_edit_comment' => 5,
                'time_before_edit_reply' => 5,
                'time_before_edit_topic' => 5,
                'time_before_delete_article' => 5,
                'time_before_delete_question' => 5,
                'time_before_delete_answer' => 5,
                'time_before_delete_comment' => 5,
                'time_before_delete_reply' => 5,
                'time_before_delete_topic' => 5,
                'ability_edit_article_only_no_comment' => 1,
                'ability_edit_question_only_no_answer' => 1,
                'ability_edit_answer_only_no_comment' => 1,
                'ability_edit_question_only_no_comment' => 1,
                'ability_edit_comment_only_no_reply' => 1,
                'ability_edit_reply_only_no_reply' => 1,
                'ability_edit_topic_only_no_article_or_question' => 1,
                'ability_delete_article_only_no_comment' => 1,
                'ability_delete_question_only_no_answer' => 1,
                'ability_delete_answer_only_no_comment' => 1,
                'ability_delete_question_only_no_comment' => 1,
                'ability_delete_comment_only_no_reply' => 1,
                'ability_delete_reply_only_no_reply' => 1,
                'ability_delete_topic_only_no_article_or_question' => 1,
                'ability_edit_own_info' => 1,
                'ability_vote' => 1,
            ]),
        ], ['user_group_id']);
    }

    // ====== 内部工具 ======

    private function lockPath(): string
    {
        return storage_path(self::LOCK_FILE);
    }

    private function envFileWritable(): bool
    {
        $path = base_path('.env');
        if (! file_exists($path)) {
            return is_writable(base_path());
        }
        return is_writable($path);
    }

    private function ensureEnvFile(): bool
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            return $this->envFileWritable();
        }

        $template = base_path('.env.example');
        if (! is_readable($template) || ! is_writable(base_path())) {
            return false;
        }

        return copy($template, $path) && $this->envFileWritable();
    }

    /**
     * 用正则把 key=value 写入 .env；key 不存在时追加。
     * 只允许写 ENV_KEYS 白名单内的键，防止任意篡改 .env。
     */
    private function updateEnv(array $values): void
    {
        $path = base_path('.env');
        $content = file_exists($path) ? file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            if (! in_array($key, self::ENV_KEYS, true)) {
                throw new RuntimeException("不允许写入的 .env 键：{$key}");
            }

            // 值含空格或特殊字符时用双引号包裹
            $escaped = $this->formatEnvValue((string) $value);

            // 已存在该 key（行首匹配）
            $pattern = '/^' . preg_quote($key, '/') . '\s*=.*$/m';
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$escaped}", $content);
            } else {
                // 追加到文件末尾，保证换行
                if ($content && ! str_ends_with($content, "\n")) {
                    $content .= "\n";
                }
                $content .= "{$key}={$escaped}\n";
            }
        }

        if (file_put_contents($path, $content, LOCK_EX) === false) {
            throw new RuntimeException("无法写入 .env 文件：{$path}");
        }
    }

    /**
     * .env 值的转义规则：含空格或 # 等保留字符时用双引号包裹，并转义双引号。
     */
    private function formatEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (preg_match('/\s|#|"|\'/', $value)) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }
        return $value;
    }

    /**
     * 把 .env 重新加载到当前进程（用 Dotenv 解析后写回 $_ENV/env()）。
     * 用于 run 步骤让 migrate/seed 能拿到新数据库连接。
     */
    private function reloadEnv(): void
    {
        // 重新引导 Laravel config（避免缓存值）
        $app = app();
        $app->make(\Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class)->bootstrap($app);
        $app->make(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class)->bootstrap($app);

        // 强制 DB 重新连接：清掉旧连接实例
        try {
            $app->make('db')->purge();
        } catch (Throwable $e) {
            // 容错：DB facade 尚未初始化时忽略
        }
    }
}
