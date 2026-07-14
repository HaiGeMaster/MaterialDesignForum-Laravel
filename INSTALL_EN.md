# MaterialDesignForum Installation Guide

## Requirements

Before starting, ensure your server meets the following requirements:

| Item | Requirement |
|------|-------------|
| PHP | ≥ 8.1 |

### Required PHP Extensions

- PDO
- Mbstring
- OpenSSL
- JSON
- Fileinfo
- Tokenizer
- Ctype
- XML
- GD (for image processing)

### Directory Permissions

The installer requires write access to the following directories/files:

- `storage/`
- `bootstrap/cache/`
- `.env`

> 💡 Use `chmod -R 755 storage bootstrap/cache && chmod 644 .env` to set permissions.

---

## Installation Steps

### 1. Deploy Files

Extract the deployment package to your website root directory.

```
/www/wwwroot/your-site/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/          ← document root
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
├── artisan
└── ...
```

### 2. Set Document Root

Point the web server's document root to the `public/` directory.

**Nginx example:**

```
root /www/wwwroot/your-site/public;
```

**Apache example:**

```
DocumentRoot "/www/wwwroot/your-site/public"
```

### 3. Configure URL Rewriting

#### Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

#### Apache

Ensure the `.htaccess` file exists in the `public/` directory with the following content:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>
```

### 4. Launch the Installer

After completing the above configuration, visit the following URL in your browser:

```
https://your-domain.com/install
```

The installation wizard will guide you through the steps below.

---

## Installation Wizard Guide

The wizard walks you through **5 steps**:

### Step 1 · Environment Check

The system automatically checks the PHP version, required extensions, and directory writability. Each item shows a green checkmark when it passes.

- Click the expand panel for specific fix suggestions if any item fails.
- All items must pass before proceeding to the next step.

### Step 2 · Database Configuration

Enter your MySQL/MariaDB database connection details:

| Field | Description | Default |
|-------|-------------|---------|
| Host | Database host address | `127.0.0.1` |
| Port | Database port | `3306` |
| Database | Name of the pre-created database | - |
| Username | Database username | `root` |
| Password | Database password | - |

> 💡 Create the database beforehand. Click **Test Connection** to verify before continuing.

### Step 3 · Site Settings

| Field | Description |
|-------|-------------|
| Site Name | The name of your forum |
| Site URL | Forum access URL (auto-filled with current domain) |

### Step 4 · Admin Account

Create the super administrator account:

| Field | Description |
|-------|-------------|
| Username | Admin login username |
| Email | Admin email address |
| Password | At least 8 characters |
| Confirm Password | Re-enter password, must match |

### Step 5 · Install

Click the **Install** button, and the system will:

1. Save database configuration
2. Run database migrations (create tables)
3. Create the admin account
4. Save site settings

Installation progress is displayed in real time. Click **Enter Homepage** when finished to access the forum.

> ⚠️ Do not close the browser page during installation. It is recommended to delete or disable the install route after installation for security.

---

## FAQ

### Environment Check Failed

Follow the on-screen prompts to fix the issues. Common causes:

- **Extension not enabled**: Edit `php.ini`, uncomment the relevant extension (e.g. `extension=gd`), then restart PHP.
- **Directory not writable**: Run `chmod -R 755 storage bootstrap/cache` and ensure the `.env` file exists and is writable.

### Database Connection Failed

- Ensure the database service is running.
- Ensure the database has been created beforehand.
- Verify the host address and port are correct (use internal address for cloud databases).

### Cannot Access After Installation

- Verify URL rewriting rules are configured correctly.
- Verify the `.env` file has been generated.
- Check `storage/` directory permissions.

### Interface Language

The installation wizard provides a language switcher in the top-right corner, supporting:
简体中文, 繁體中文, English (US), English (UK), Deutsch, Français, 日本語, 한국어, Русский.

The interface also supports dark/light theme switching (follows system settings by default).
