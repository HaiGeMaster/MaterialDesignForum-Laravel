<?php

/**
 * Author HaiGeMaster
 * @package MaterialDesignForum
 * @link https://github.com/HaiGeMaster
 * @copyright Copyright (c) 2023 HaiGeMaster
 * @start-date 2023/05/20-15:53:29
 */

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class InstallController extends Controller
{
    /**
     * 安装页面
     */
    public function index()
    {
        // 防止重复安装
        if ($this->alreadyInstalled()) {
            abort(404);
        }

        return view('install.index', [
            'requirements' => $this->checkRequirements(),
        ]);
    }

    /**
     * 执行安装
     */
    public function store(Request $request)
    {
        if ($this->alreadyInstalled()) {
            abort(403, '系统已安装');
        }

        $validator = Validator::make($request->all(), [
            'db_host'     => 'required',
            'db_port'     => 'required|numeric',
            'db_database' => 'required',
            'db_username' => 'required',
            'db_password' => 'nullable',

            'admin_name'     => 'required',
            'admin_email'    => 'required|email',
            'admin_password'=> 'required|min:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // 1️⃣ 写入 .env
            $this->updateEnv($request);

            // 2️⃣ 重新加载配置
            Artisan::call('config:clear');

            // 3️⃣ 测试数据库连接
            DB::purge('mysql');
            DB::connection()->getPdo();

            // 4️⃣ 执行迁移
            Artisan::call('migrate:fresh --force');

            // 5️⃣ 创建管理员
            DB::table('users')->insert([
                'name'       => $request->admin_name,
                'email'      => $request->admin_email,
                'password'   => Hash::make($request->admin_password),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 6️⃣ 安装完成标记
            file_put_contents(
                storage_path('installed.lock'),
                json_encode([
                    'installed_at' => now()->toDateTimeString(),
                    'version' => config('app.version', '1.0.0'),
                ], JSON_PRETTY_PRINT)
            );

            return redirect('/')->with('success', '安装成功');

        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * 是否已安装
     */
    protected function alreadyInstalled(): bool
    {
        return file_exists(storage_path('installed.lock'));
    }

    /**
     * 环境检测
     */
    protected function checkRequirements(): array
    {
        return [
            'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'PDO' => extension_loaded('pdo'),
            'Mbstring' => extension_loaded('mbstring'),
            'OpenSSL' => extension_loaded('openssl'),
            'storage writable' => is_writable(storage_path()),
            '.env writable' => is_writable(base_path('.env')),
        ];
    }

    /**
     * 写入 .env
     */
    protected function updateEnv(Request $request): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        $replacements = [
            'DB_HOST'     => $request->db_host,
            'DB_PORT'     => $request->db_port,
            'DB_DATABASE'=> $request->db_database,
            'DB_USERNAME'=> $request->db_username,
            'DB_PASSWORD'=> $request->db_password,
        ];

        foreach ($replacements as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}=" . addslashes($value);

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= PHP_EOL . $replacement;
            }
        }

        file_put_contents($envPath, $envContent);
    }
}