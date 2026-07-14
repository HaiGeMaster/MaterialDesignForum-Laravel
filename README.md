
# MaterialDesignForum 指引页面

<details>
<summary>
1.介绍
</summary>

# 介绍

<img src="https://www.xbedrock.com/assets/info_content/md3/device_info_template_auto.png" alt="MDUI2主题预览图" width="100%">

<img src="https://www.xbedrock.com/assets/info_content/md2/device_info_template_auto.png" alt="Vuetify2主题预览图" width="100%">

# [CN] Material Design Forum - 现代化网页论坛应用

## 产品概述

Material Design Forum是一款基于网页的论坛应用程序，致力于为用户提供：

- 卓越的交互体验
- 视觉享受
- 符合Material Design核心理念的界面设计

## UI设计与技术实现

### 框架与主题

- **前端框架**：Vuetify4
- **客户端主题**：Vuetify4
- **设计规范**：严格遵循Material Design

### 响应式布局

- 支持设备类型：
    - PC（桌面端）
    - Pad（平板设备）
    - Mobile（移动设备）
- 特点：
    - 智能识别设备类型
    - 浏览器窗口自适应
    - 无缝布局切换

## 核心功能

### 用户功能

- 内容发布：
    - 发起话题
    - 提出问题
    - 撰写文章
- 互动功能：
    - 发表回答
    - 参与评论
    - 进行回复

### 管理员功能

- 内容管理：
    - 话题/提问/文章/回答/评论/回复的CRUD操作
- 后台工具：
    - 实时数据仪表盘
    - 数据管理与删除
    - 站点数据设置
    - 发信邮箱配置
- 用户组管理：
    - 精细化权限分配
    - 多样化角色管理

## 设计特色

### 视觉体验

- 色彩搭配：精心设计
- 图标系统：符合Material规范
- 动效过渡：流畅自然
- 主题模式：
    - 深色模式
    - 浅色模式

### 国际化支持

- 内置多语言选项
- 开放语言包翻译接口
- 支持自定义语言文件

## 总结

Material Design Forum通过以下优势成为现代化论坛平台：

1. 精湛的设计美学
2. 强大的功能体系
3. 灵活的自定义选项
4. 完善的多语言支持

适用于：

- Material Design爱好者
- 社区管理员
- 全球化用户群体

> 让我们共同打造更美好的线上社区环境！

</details>

<details>
<summary>
2.安装指南
</summary>

# 安装指南

## 环境要求

在开始安装之前，请确保你的服务器满足以下要求：

| 项目 | 要求  |
| ---- | ----- |
| PHP  | ≥ 8.1 |

### 必需 PHP 扩展

- PDO
- Mbstring
- OpenSSL
- JSON
- Fileinfo
- Tokenizer
- Ctype
- XML
- GD（用于图片处理）

### 目录权限

安装程序需要以下目录/文件可写：

- `storage/`
- `bootstrap/cache/`
- `.env`

> 💡 可以使用 `chmod -R 755 storage bootstrap/cache && chmod 644 .env` 设置权限。

---

## 安装步骤

### 1. 部署文件

将部署包解压至你的网站根目录。

```
/www/wwwroot/your-site/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/          ← 运行目录
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
├── artisan
└── ...
```

### 2. 设置运行目录

将网站运行目录设置为 `public/`。

**Nginx 示例：**

```
root /www/wwwroot/your-site/public;
```

**Apache 示例：**

```
DocumentRoot "/www/wwwroot/your-site/public"
```

### 3. 配置伪静态

#### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

#### Apache

确保 `.htaccess` 文件存在于 `public/` 目录中，内容如下：

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>
```

### 4. 启动安装向导

完成以上配置后，在浏览器中访问：

```
https://your-domain.com/install
```

安装向导将会引导你完成以下步骤。

---

## 安装向导使用说明

安装向导会依次引导你完成 **5 个步骤**：

### Step 1 · 环境检测

系统会自动检测 PHP 版本、扩展是否加载、目录是否可写。每一项通过后会显示绿色对勾标记。

- 如果某项不满足要求，点击展开面板可查看具体修复建议。
- 必须所有项通过后才能进入下一步。

### Step 2 · 数据库配置

填写你的 MySQL/MariaDB 数据库连接信息：

| 字段       | 说明               | 默认值      |
| ---------- | ------------------ | ----------- |
| 数据库地址 | 数据库主机地址     | `127.0.0.1` |
| 端口       | 数据库端口         | `3306`      |
| 数据库名   | 已创建的数据库名称 | -           |
| 用户名     | 数据库用户名       | `root`      |
| 密码       | 数据库密码         | -           |

> 💡 请提前创建好数据库。填写完成后点击 **测试连接** 验证配置是否正确。连接成功后才能继续。

### Step 3 · 站点设置

| 字段     | 说明                                 |
| -------- | ------------------------------------ |
| 站点名称 | 论坛网站的名称                       |
| 站点地址 | 论坛访问地址（默认自动填充当前域名） |

### Step 4 · 管理员账号

创建论坛的超级管理员账户：

| 字段         | 说明                     |
| ------------ | ------------------------ |
| 管理员用户名 | 登录后台的用户名         |
| 邮箱         | 管理员邮箱地址           |
| 密码         | 至少 8 个字符            |
| 确认密码     | 再次输入密码，需保持一致 |

### Step 5 · 开始安装

点击 **开始安装** 按钮后，系统会依次执行：

1. 保存数据库配置
2. 运行数据库迁移（创建数据表）
3. 创建管理员账号
4. 保存站点设置

安装进度会实时显示，完成后点击 **进入首页** 即可访问论坛。

> ⚠️ 安装过程中请勿关闭浏览器页面。安装完成后建议删除或禁用安装路由以确保安全。

---

## 常见问题

### 环境检测未通过

根据页面提示检查并修复对应问题。常见原因：

- **扩展未启用**：编辑 `php.ini`，取消对应扩展的注释（如 `extension=gd`），然后重启 PHP。
- **目录不可写**：执行 `chmod -R 755 storage bootstrap/cache` 并确保 `.env` 文件存在且可写。

### 数据库连接失败

- 确认数据库服务已启动。
- 确认已提前创建好数据库。
- 检查主机地址和端口是否正确（云数据库请使用内网地址）。

### 安装完成后无法访问

- 确认伪静态配置正确。
- 确认 `.env` 文件已生成。
- 检查 `storage/` 目录权限。

### 界面语言

安装向导右上角提供语言切换菜单，支持以下语言：
简体中文、繁體中文、English (US)、English (UK)、Deutsch、Français、日本語、한국어、Русский。

界面也支持深色/浅色主题切换（默认跟随系统设置）。

</details>

<details>
<summary>
3.OAuth2 第三方登录配置指南
</summary>

# 第三方登录配置指南

> **重要提示**
>
> 1. **Google OAuth 境内不可用**：由于网络策略限制，Google 服务在中国大陆境内无法直接访问。若您的服务器或用户群体主要位于境内，请勿启用 Google 登录；若部署在境外网络环境，可参考第 4 节进行配置。
> 2. **安全警告**：客户端密钥（Client Secret）等同于密码，严禁在前端代码或公开仓库中暴露，生产环境请务必使用 HTTPS 回调地址。

本指南适用于MDF管理后台，介绍如何配置 管理后台 -> 设置 -> OAuth 授权登录 **Microsoft Entra ID (原 Azure AD)**、**GitHub** 以及 **Google** 的 OAuth 集成。

---

## 1. Microsoft Entra ID (Azure AD) 应用注册

### 1.1 注册流程

1. 访问 https://entra.microsoft.com/#view/Microsoft_AAD_RegisteredApps/ApplicationsListBlade。
2. 点击 **“新注册”**，填写以下参数：
    - **名称**：输入您的应用名称（如 `MDF-Prod`）。
    - **受支持的账户类型**：建议选择 **“任何组织目录(任何 Microsoft Entra ID 租户 - 多租户)”**。若需支持 Skype/Xbox 等个人账号，可选择包含“个人 Microsoft 账户”的选项。
    - **重定向 URI（可选）**：
        - 平台类型：选择 **Web**。
        - 回调地址：`https://<您的域名>/auth/microsoft/redirect`
            > 注：若此处未填写，后续可在“管理” -> “身份验证” -> “平台配置” -> “Web” -> “重定向 URI”中补充。

### 1.2 权限配置

1. 进入应用详情页，点击 **“API 权限”**。
2. 点击 **“添加权限”** -> **“Microsoft Graph”** -> **“委托的权限”**。
3. 添加以下权限以确保基本登录功能：
    - `openid`（OpenID Connect 基础协议）
    - `profile`（获取基础用户画像，如姓名）
    - `User.Read`（读取登录用户的基本信息）

### 1.3 应用元数据配置

在 **“品牌和属性”** 中完善应用信息，建议上传应用图标、填写应用名称及隐私政策链接，以提升企业用户的信任度。

### 1.4 凭据获取

1. **客户端 ID (Client ID)**：
    - 在“应用注册” -> “所有应用程序”列表中即可直接查看 **“应用程序(客户端) ID”**。
2. **客户端密钥 (Client Secret)**：
    - 进入 **“证书和密码”** -> **“新建客户端密码”**。
    - 添加描述并选择有效期，点击“添加”。
    - **重要**：生成后的值仅显示一次，请立即复制保存，刷新页面后将无法再次查看。

---

## 2. GitHub OAuth 应用注册

### 2.1 注册流程

1. 访问 https://github.com/settings/developers（Settings -> Developer settings -> OAuth Apps）。
2. 点击 **“New OAuth App”**，填写以下字段：
    - **Application name**：应用名称（如 `MDF登录集成`）。
    - **Homepage URL**：您的网站首页域名（如 `https://<您的域名>`）。
    - **Authorization callback URL**（回调地址）：`https://<您的域名>/auth/github/redirect`
        > 注意：GitHub 对 URL 校验严格，请确保 HTTP/HTTPS 与实际部署完全一致。

### 2.2 凭据获取

注册成功后，页面将展示：

- **Client ID**：明文显示，可直接复制。
- **Client Secret**：点击 **“Generate a new client secret”** 生成。生成后请立即复制保存。

---

## 3. Google OAuth 应用注册（境外环境适用）

> **前置条件**：请确保运行环境可正常访问 Google 服务。

### 3.1 创建项目与配置同意屏幕

1. 访问 https://console.cloud.google.com/。
2. **创建项目**：点击顶部项目选择器 -> **“新建项目”**，填写项目名称（如 `MDF-OAuth`）并创建。
3. **配置 OAuth 同意屏幕**：
    - 导航至 **“API 和服务”** -> **“OAuth 同意屏幕”**。
    - **用户类型**：选择 **“外部”**（External），允许所有 Google 账号登录。
    - **应用信息**：填写应用名称、用户支持邮箱及开发者联系信息。
    - **范围（Scopes）**：点击“添加或移除范围”，勾选以下核心权限：
        - `.../auth/userinfo.email`（查看邮箱）
        - `.../auth/userinfo.profile`（查看基本资料）
        - `openid`
    - **测试用户**（仅限发布前）：添加允许用于测试的 Google 账号。

### 3.2 创建 Web 客户端凭据

1. 进入 **“API 和服务”** -> **“凭据”**。
2. 点击 **“创建凭据”** -> **“OAuth 客户端 ID”**。
3. **应用类型**：选择 **“Web 应用”**。
4. **名称**：填写标识名（如 `MDF-Web-Client`）。
5. **已获授权的重定向 URI**：
    - 点击 **“+ 添加 URI”**，填写 MDF 系统的回调地址：`https://<您的域名>/auth/google/redirect`
      （必须与 MDF 后台配置完全一致）

### 3.3 凭据获取

点击创建后，系统将弹出凭据窗口：

- **客户端 ID**：格式通常为 `xxxx.apps.googleusercontent.com`。
- **客户端密钥**：点击右侧图标查看，请立即复制保存（仅显示一次）。
- 若关闭弹窗，可在“凭据”页面重新生成新的客户端密钥。

---

## 4. MDF 系统集成配置

1. 登录 **MDF 后台管理系统**。
2. 导航至：**设置** -> **OAuth 授权登录设置**。
3. 根据需求填写已获取的凭据：
    - **Microsoft OAuth**：填入 Client ID 与 Client Secret。
    - **GitHub OAuth**：填入 Client ID 与 Client Secret。
    - **Google OAuth**：填入 Client ID 与 Client Secret（仅限境外网络环境）。
4. 点击 **保存**。
5. 返回登录页，点击相应的第三方图标，验证是否能正常跳转并完成登录流程。

---

## 5. 注意事项与故障排查

| 事项               | 说明                                                                                                    |
| :----------------- | :------------------------------------------------------------------------------------------------------ |
| **网络连通性**     | Google OAuth 需服务器具备访问 `accounts.google.com` 的能力；GitHub 和 Microsoft 通常无特殊网络要求。    |
| **回调地址一致性** | 三方平台配置的 Redirect URI 必须与 MDF 后台填写的地址完全一致（包括大小写、`http`/`https`、末尾斜杠）。 |
| **权限不足**       | 若登录报错提示权限不足，请检查 Microsoft/GitHub/Google 后台的 API 权限列表是否已按要求授予。            |
| **密钥泄露**       | 如发现 Client Secret 疑似泄露，请立即在对应平台删除旧密钥并生成新密钥。                                 |

</details>
