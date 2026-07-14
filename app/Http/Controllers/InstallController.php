<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Http\Controllers;

use App\Models\Option;
use App\Models\User;
use App\Models\UserGroup;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InstallController extends Controller
{
    /**
     * 安装向导首页
     */
    public function index()
    {
        if ($this->alreadyInstalled()) {
            abort(404);
        }

        return view('install', [
            'version'    => $this->getAppVersion(),
            'locale'     => app()->getLocale(),
            'phpVersion' => PHP_VERSION,
            'envCheck'   => $this->checkRequirements(),
        ]);
    }

    // ==================== 分步安装 API ====================

    /**
     * 测试数据库连接
     */
    public function testDb(Request $request)
    {
        if ($this->alreadyInstalled()) {
            return response()->json(['ok' => false, 'message' => '已安装']);
        }

        try {
            $host     = $request->input('host', '127.0.0.1');
            $port     = $request->input('port', '3306');
            $database = $request->input('database', '');
            $username = $request->input('username', 'root');
            $password = $request->input('password', '');

            $dsn = "mysql:host={$host};port={$port};dbname={$database}";
            new \PDO($dsn, $username, $password, [
                \PDO::ATTR_TIMEOUT => 5,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            return response()->json(['ok' => true]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Step 1: 保存 .env 数据库配置
     */
    public function saveDb(Request $request)
    {
        if ($this->alreadyInstalled()) {
            return response()->json(['ok' => false, 'message' => '已安装']);
        }

        try {
            $this->updateEnv([
                'DB_HOST'     => $request->input('db.host', '127.0.0.1'),
                'DB_PORT'     => $request->input('db.port', '3306'),
                'DB_DATABASE' => $request->input('db.database', ''),
                'DB_USERNAME' => $request->input('db.username', 'root'),
                'DB_PASSWORD' => $request->input('db.password', ''),
            ]);

            // 重新加载配置，使新的 DB 配置生效
            Artisan::call('config:clear');
            DB::purge('mysql');
            DB::reconnect('mysql');

            return response()->json(['ok' => true]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Step 2: 运行数据库迁移
     */
    public function migrate(Request $request)
    {
        if ($this->alreadyInstalled()) {
            return response()->json(['ok' => false, 'message' => '已安装']);
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            // 检查是否有错误
            if (str_contains($output, 'Exception') || str_contains($output, 'error')) {
                return response()->json(['ok' => false, 'message' => $output]);
            }

            return response()->json(['ok' => true, 'output' => $output]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Step 3: 创建管理员账号
     */
    public function createAdmin(Request $request)
    {
        if ($this->alreadyInstalled()) {
            return response()->json(['ok' => false, 'message' => '已安装']);
        }

        try {
            $adminData = $request->input('admin', []);

            $user = new User();
            $user->username    = $adminData['username'] ?? 'admin';
            $user->email       = $adminData['email'] ?? 'admin@localhost';
            $user->password    = md5($adminData['password']); // 项目使用 md5
            $user->avatar      = User::CreateDefaultAvatar($user->username);
            $user->cover       = User::CreateDefaultCover();
            $user->create_ip   = $request->ip();
            $user->create_time = now()->timestamp;
            $user->update_time = now()->timestamp;
            $user->user_group_id = 1; // 管理员组
            $user->save();

            UserGroup::AddUserGroupUserCount(1);

            return response()->json(['ok' => true]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Step 4: 保存站点设置
     */
    public function saveSite(Request $request)
    {
        if ($this->alreadyInstalled()) {
            return response()->json(['ok' => false, 'message' => '已安装']);
        }

        try {
            $siteData = $request->input('site', []);

            // 保存站点名称
            if (!empty($siteData['name'])) {
                Option::Set('site_name', $siteData['name']);
            }

            // 保存站点 URL
            if (!empty($siteData['url'])) {
                $this->updateEnv([
                    'APP_URL' => $siteData['url'],
                ]);
            }

            // 写入安装标记
            $this->markInstalled();

            return response()->json(['ok' => true]);
        } catch (Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    // ==================== 辅助方法 ====================

    /**
     * 从 composer.json 获取应用版本号
     */
    protected function getAppVersion(): string
    {
        $composerFile = base_path('composer.json');

        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            if (!empty($composer['version'])) {
                return $composer['version'];
            }
        }

        return '1.0.0';
    }

    /**
     * 是否已安装
     */
    protected function alreadyInstalled(): bool
    {
        return file_exists(storage_path('installed.lock'));
    }

    /**
     * 环境检测（返回给前端）
     */
    protected function checkRequirements(): array
    {
        return [
            'php_version'        => PHP_VERSION,
            'php_ok'             => version_compare(PHP_VERSION, '8.1.0', '>='),
            'pdo'                => extension_loaded('pdo'),
            'mbstring'           => extension_loaded('mbstring'),
            'openssl'            => extension_loaded('openssl'),
            'json'               => extension_loaded('json'),
            'fileinfo'           => extension_loaded('fileinfo'),
            'tokenizer'          => extension_loaded('tokenizer'),
            'ctype'              => extension_loaded('ctype'),
            'xml'                => extension_loaded('xml'),
            'gd'                 => extension_loaded('gd'),
            'storage_writable'   => is_writable(storage_path()),
            'bootstrap_writable' => is_writable(base_path('bootstrap/cache')),
            'env_writable'       => is_writable(base_path('.env')),
        ];
    }

    /**
     * 写入 .env
     */
    protected function updateEnv(array $replacements): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $envPath = base_path('.env.example');
            copy($envPath, base_path('.env'));
            $envPath = base_path('.env');
        }

        $envContent = file_get_contents($envPath);

        foreach ($replacements as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}=" . addslashes($value);

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent, 1);
            } else {
                $envContent .= PHP_EOL . $replacement;
            }
        }

        file_put_contents($envPath, $envContent);
    }

    /**
     * 写入安装完成标记
     */
    protected function markInstalled(): void
    {
        file_put_contents(
            storage_path('installed.lock'),
            json_encode([
                'installed_at' => now()->toDateTimeString(),
                'version'      => $this->getAppVersion(),
            ], JSON_PRETTY_PRINT)
        );
    }
}
