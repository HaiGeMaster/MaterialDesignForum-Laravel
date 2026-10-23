# MDForum

基于 Laravel 13 + Vue 3 + Vuetify 4 的 Material Design 风格论坛系统。

## 技术栈

### 后端
- **PHP** ≥ 8.3
- **Laravel** 13.x
- **数据库** MySQL（推荐 8.0+）/ SQLite
- **依赖管理** Composer
- **关键扩展**：pdo_mysql、openssl、mbstring、fileinfo、gd/imagick（图片处理）

### 前端
- **Vue** 3.5
- **Vuetify** 4.1（Material Design 组件库）
- **Vite** 8（构建工具）
- **Pinia** 4（状态管理）
- **Vue Router** 5
- **Vue I18n** 11（多语言：简中 / 英文）
- **TipTap** 3（富文本编辑器）
- **ECharts** 6（图表）
- **Tailwind CSS** 4
- **依赖管理** pnpm（推荐）/ npm

## 目录结构

```
├── app/                  # 后端 PHP 源码
│   ├── Http/Controllers/ # 控制器
│   ├── Http/Middleware/  # 中间件
│   ├── Models/           # Eloquent 模型
│   ├── Services/         # 业务服务
│   └── Support/          # 辅助工具
├── bootstrap/            # 应用引导
├── config/               # 配置文件
├── database/
│   ├── migrations/       # 数据库迁移
│   ├── seeders/          # 填充数据
│   └── factories/        # 模型工厂
├── lang/                 # 后端语言包（邮件模板等）
├── public/               # Web 入口
│   ├── build/            # 前端构建产物（vite build 生成）
│   ├── index.php         # Laravel 入口
│   ├── .htaccess         # Apache 重写规则
│   └── nginx.htaccess    # Nginx 重写规则（被 vhost include）
├── resources/
│   ├── css/              # 样式源码
│   ├── js/               # 前端源码
│   │   ├── components/   # Vue 组件
│   │   ├── views/        # 页面视图
│   │   ├── stores/       # Pinia 状态
│   │   ├── router/       # 路由配置
│   │   ├── locales/      # 前端语言包（zh.json / en.json）
│   │   └── api.js        # Axios 封装
│   └── views/            # Blade 模板（welcome / admin / 邮件）
├── routes/
│   ├── api.php           # API 路由
│   ├── web.php           # Web 路由
│   └── console.php       # 控制台路由
├── storage/              # 运行时存储（缓存/日志/上传）
├── artisan               # Laravel 命令行入口
├── composer.json         # 后端依赖声明
├── package.json          # 前端依赖声明
├── vite.config.js        # Vite 构建配置
└── .env.example          # 环境变量模板
```

## 环境要求

- PHP ≥ 8.3（需开启 pdo_mysql、openssl、mbstring、fileinfo 扩展）
- Composer ≥ 2.x
- Node.js ≥ 20（推荐 22 LTS）
- pnpm ≥ 9（或使用 npm）
- MySQL ≥ 8.0（或 SQLite）
- Web 服务器：Nginx / Apache（开发环境可直接用 `php artisan serve`）

## 快速开始

### 1. 克隆项目

```bash
git clone <仓库地址> MDForumLaravel
cd MDForumLaravel
```

### 2. 安装后端依赖

```bash
composer install
```

> 生产环境部署时建议加 `--no-dev --optimize-autoloader` 减小体积。

### 3. 安装前端依赖

```bash
# 推荐 pnpm
pnpm install

# 或使用 npm
npm install
```

### 4. 启动服务并访问安装向导

```bash
php artisan serve
```

浏览器访问 `http://localhost:8000`，会自动跳转到 `/install` 安装向导，按界面提示依次完成：

1. **环境检查** — PHP 版本、扩展、目录权限
2. **数据库配置** — 填写 MySQL 连接信息（向导会测试连接并写入 `.env`）
3. **应用配置** — 站点名称、域名地址、时区、邮件 SMTP
4. **创建管理员账号** — 用户名、邮箱、密码
5. **执行安装** — 自动 migrate + 写入安装锁文件

向导会自动生成 `APP_KEY` 并把所有配置写入 `.env`，无需手动编辑配置文件。

> 如果需要手动配置（如开发调试），可参考 `.env.example` 复制为 `.env` 后编辑，再执行 `php artisan key:generate`。

## 常用命令

```bash
# 后端
php artisan serve              # 启动开发服务器
php artisan migrate            # 执行迁移 新增/修改表结构
php artisan migrate:rollback   # 回滚上一次迁移
php artisan db:seed            # 填充示例数据
php artisan tinker             # 交互式 REPL
php artisan route:list         # 查看所有路由
php artisan config:cache       # 缓存配置（生产环境）
php artisan route:cache        # 缓存路由（生产环境）
php artisan storage:link       # 创建 public/storage 软链

# 前端
pnpm dev                       # 开发模式（HMR 热更新）
pnpm build                     # 生产构建
pnpm install                   # 安装依赖

# 打包部署（Windows）
build.bat                      # 打包成 .7z 部署包
```

## 部署打包

Windows 环境下运行 `build.bat` 可生成全量离线部署包：

```cmd
build.bat
```

产物：`MDF-APP-Laravel_yyyyMMddHHmmss.7z`，包含：
- 后端源码 + vendor（生产依赖）
- 前端构建产物 `public/build/`
- 数据库迁移、配置、视图
- `storage/` 目录骨架（保留 .gitignore，清空运行时文件）

排除：`.env`、`node_modules`、`.git`、`tests`、`scripts`、`dist`、运行时缓存/日志、用户上传文件。

## 后台管理

访问 `/admin` 进入后台管理界面（需 `is_admin` 权限），可管理：
- 用户 / 用户组
- 话题 / 问题 / 文章 / 回答 / 评论 / 回复
- 举报处理
- 站点设置

## 第三方登录

支持 GitHub / Google / Microsoft OAuth 登录，在 `.env` 配置对应凭证即可启用：

```env
GITHUB_CLIENT_ID=
GITHUB_CLIENT_SECRET=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
MICROSOFT_TENANT=common
```

回调 URL：`${APP_URL}/oauth/callback/{provider}`（不含 `/api` 前缀）。

## License

MIT License
