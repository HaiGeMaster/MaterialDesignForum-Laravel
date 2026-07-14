
# MaterialDesignForum Guide

<details open>
<summary>
1. Introduction
</summary>

# Introduction

<img src="https://www.xbedrock.com/assets/info_content/md3/device_info_template_auto.png" alt="MDUI2 Theme Preview" width="100%">

<img src="https://www.xbedrock.com/assets/info_content/md2/device_info_template_auto.png" alt="Vuetify2 Theme Preview" width="100%">

# Material Design Forum - Modern Web Forum Application

## Product Overview

Material Design Forum is a web-based forum application dedicated to providing users with:

- Excellent interactive experience
- Visual enjoyment
- Interface design that conforms to Material Design core principles

## UI Design & Technical Implementation

### Framework & Themes

- **Frontend Framework**: Vuetify4
- **Client Theme**: Vuetify4
- **Design Standard**: Strictly follows Material Design

### Responsive Layout

- Supported device types:
    - PC (Desktop)
    - Pad (Tablet)
    - Mobile
- Features:
    - Intelligent device type recognition
    - Browser window adaptation
    - Seamless layout switching

## Core Features

### User Features

- Content publishing:
    - Start discussions
    - Ask questions
    - Write articles
- Interaction features:
    - Post answers
    - Leave comments
    - Reply to posts

### Admin Features

- Content management:
    - CRUD operations for topics/questions/articles/answers/comments/replies
- Admin tools:
    - Real-time data dashboard
    - Data management & deletion
    - Site settings
    - Email configuration
- User group management:
    - Granular permission assignment
    - Multi-role management

## Design Highlights

### Visual Experience

- Color schemes: Carefully crafted
- Icon system: Material Design compliant
- Motion transitions: Smooth and natural
- Theme modes:
    - Dark mode
    - Light mode

### Internationalization

- Built-in multi-language options
- Open language pack translation interface
- Supports custom language files

## Summary

Material Design Forum stands out as a modern forum platform through:

1. Exquisite design aesthetics
2. Robust feature system
3. Flexible customization options
4. Comprehensive multi-language support

Suitable for:

- Material Design enthusiasts
- Community administrators
- Global user base

> Let's build a better online community together!

</details>

<details>
<summary>
2. Installation Guide
</summary>

# Installation Guide

## Requirements

Before starting, ensure your server meets the following requirements:

| Item | Requirement |
| ---- | ----------- |
| PHP  | ≥ 8.1       |

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

The installer requires the following directories/files to be writable:

- `storage/`
- `bootstrap/cache/`
- `.env`

> 💡 You can use `chmod -R 755 storage bootstrap/cache && chmod 644 .env` to set permissions.

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
├── public/          ← Document root
├── resources/
├── routes/
├── storage/
├── vendor/
├── .env
├── artisan
└── ...
```

### 2. Set Document Root

Point the website document root to `public/`.

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

### 4. Launch Installation Wizard

Once the above configuration is complete, visit in your browser:

```
https://your-domain.com/install
```

The installation wizard will guide you through the following steps.

---

## Installation Wizard Guide

The wizard will guide you through **5 steps** in sequence:

### Step 1 · Environment Check

The system automatically checks PHP version, loaded extensions, and writable directories. Each passed item shows a green check mark.

- If an item fails, click to expand the panel to view specific fix suggestions.
- All items must pass before proceeding to the next step.

### Step 2 · Database Configuration

Fill in your MySQL/MariaDB connection details:

| Field        | Description              | Default     |
| ------------ | ------------------------ | ----------- |
| Host         | Database host address    | `127.0.0.1` |
| Port         | Database port            | `3306`      |
| Database     | Pre-created database name | -          |
| Username     | Database username        | `root`      |
| Password     | Database password        | -           |

> 💡 Please create the database in advance. After filling in the details, click **Test Connection** to verify. Connection must succeed before proceeding.

### Step 3 · Site Settings

| Field      | Description                                      |
| ---------- | ------------------------------------------------ |
| Site Name  | Name of your forum site                          |
| Site URL   | Forum access URL (auto-filled with current domain) |

### Step 4 · Admin Account

Create a super administrator account for the forum:

| Field           | Description                          |
| --------------- | ------------------------------------ |
| Admin Username  | Username for admin panel login       |
| Email           | Admin email address                  |
| Password        | At least 8 characters                |
| Confirm Password | Re-enter password, must match       |

### Step 5 · Start Installation

After clicking the **Start Installation** button, the system will sequentially:

1. Save database configuration
2. Run database migrations (create tables)
3. Create admin account
4. Save site settings

Installation progress is displayed in real-time. Once complete, click **Go to Homepage** to access the forum.

> ⚠️ Do not close the browser page during installation. After installation, we recommend removing or disabling the installation route for security.

---

## FAQ

### Environment Check Failed

Follow the on-screen prompts to check and fix the corresponding issues. Common causes:

- **Extension not enabled**: Edit `php.ini`, uncomment the relevant extension (e.g., `extension=gd`), then restart PHP.
- **Directory not writable**: Run `chmod -R 755 storage bootstrap/cache` and ensure the `.env` file exists and is writable.

### Database Connection Failed

- Confirm the database service is running.
- Confirm the database has been created in advance.
- Verify the host address and port are correct (use internal addresses for cloud databases).

### Cannot Access After Installation

- Confirm URL rewriting is configured correctly.
- Confirm the `.env` file has been generated.
- Check `storage/` directory permissions.

### Interface Language

The installation wizard provides a language switcher in the top-right corner, supporting the following languages:
简体中文, 繁體中文, English (US), English (UK), Deutsch, Français, 日本語, 한국어, Русский.

The interface also supports dark/light theme switching (defaults to system settings).

</details>

<details>
<summary>
3. OAuth2 Third-Party Login Configuration Guide
</summary>

# Third-Party Login Configuration Guide

> **Important Notes**
>
> 1. **Google OAuth is unavailable within mainland China**: Due to network policy restrictions, Google services cannot be accessed directly within mainland China. If your server or user base is primarily located within China, do not enable Google login. If deploying in an overseas network environment, refer to section 3 for configuration.
> 2. **Security Warning**: Client secrets are equivalent to passwords. Never expose them in frontend code or public repositories. Always use HTTPS callback URLs in production environments.

This guide is for MDF admin panel and explains how to configure OAuth login integration for **Microsoft Entra ID (formerly Azure AD)**, **GitHub**, and **Google** via Admin Panel → Settings → OAuth Login.

---

## 1. Microsoft Entra ID (Azure AD) App Registration

### 1.1 Registration Process

1. Visit https://entra.microsoft.com/#view/Microsoft_AAD_RegisteredApps/ApplicationsListBlade.
2. Click **"New registration"** and fill in the following parameters:
    - **Name**: Enter your application name (e.g., `MDF-Prod`).
    - **Supported account types**: We recommend selecting **"Accounts in any organizational directory (Any Microsoft Entra ID tenant - Multitenant)"**. To support Skype/Xbox personal accounts, select the option that includes "personal Microsoft accounts".
    - **Redirect URI (optional)**:
        - Platform type: Select **Web**.
        - Callback URL: `https://<your-domain>/auth/microsoft/redirect`
            > Note: If not filled in here, you can add it later under "Manage" → "Authentication" → "Platform configurations" → "Web" → "Redirect URIs".

### 1.2 Permission Configuration

1. Go to the app details page and click **"API permissions"**.
2. Click **"Add a permission"** → **"Microsoft Graph"** → **"Delegated permissions"**.
3. Add the following permissions to ensure basic login functionality:
    - `openid` (OpenID Connect base protocol)
    - `profile` (Get basic user profile, such as name)
    - `User.Read` (Read signed-in user's basic information)

### 1.3 App Metadata Configuration

Under **"Branding & properties"**, complete the app information. We recommend uploading an app icon, filling in the app name, and adding a privacy policy link to increase trust among enterprise users.

### 1.4 Credential Retrieval

1. **Client ID**:
    - View directly in "App registrations" → "All applications" list: **"Application (client) ID"**.
2. **Client Secret**:
    - Go to **"Certificates & secrets"** → **"New client secret"**.
    - Add a description, select an expiration period, and click "Add".
    - **Important**: The generated value is shown only once. Copy and save it immediately; it cannot be viewed again after refreshing the page.

---

## 2. GitHub OAuth App Registration

### 2.1 Registration Process

1. Visit https://github.com/settings/developers (Settings → Developer settings → OAuth Apps).
2. Click **"New OAuth App"** and fill in the following fields:
    - **Application name**: App name (e.g., `MDF Login Integration`).
    - **Homepage URL**: Your website's homepage domain (e.g., `https://<your-domain>`).
    - **Authorization callback URL**: `https://<your-domain>/auth/github/redirect`
        > Note: GitHub validates URLs strictly. Ensure HTTP/HTTPS matches your actual deployment exactly.

### 2.2 Credential Retrieval

After successful registration, the page will display:

- **Client ID**: Shown in plain text, can be copied directly.
- **Client Secret**: Click **"Generate a new client secret"** to create one. Copy and save it immediately after generation.

---

## 3. Google OAuth App Registration (For Overseas Environments)

> **Prerequisite**: Ensure the runtime environment can access Google services.

### 3.1 Create Project & Configure Consent Screen

1. Visit https://console.cloud.google.com/.
2. **Create Project**: Click the project selector at the top → **"New Project"**, enter a project name (e.g., `MDF-OAuth`), and create it.
3. **Configure OAuth consent screen**:
    - Go to **"APIs & Services"** → **"OAuth consent screen"**.
    - **User Type**: Select **"External"** to allow all Google accounts to log in.
    - **App Information**: Fill in the app name, user support email, and developer contact information.
    - **Scopes**: Click "Add or Remove Scopes" and select the following core permissions:
        - `.../auth/userinfo.email` (View email)
        - `.../auth/userinfo.profile` (View basic profile)
        - `openid`
    - **Test Users** (before publishing): Add Google accounts allowed for testing.

### 3.2 Create Web Client Credentials

1. Go to **"APIs & Services"** → **"Credentials"**.
2. Click **"Create Credentials"** → **"OAuth client ID"**.
3. **Application Type**: Select **"Web application"**.
4. **Name**: Enter an identifier (e.g., `MDF-Web-Client`).
5. **Authorized redirect URIs**:
    - Click **"+ Add URI"** and enter the MDF system callback URL: `https://<your-domain>/auth/google/redirect`
      (Must match the MDF admin panel configuration exactly)

### 3.3 Credential Retrieval

After creation, the system will display a credentials window:

- **Client ID**: Typically in the format `xxxx.apps.googleusercontent.com`.
- **Client Secret**: Click the icon on the right to view it. Copy and save immediately (shown only once).
- If the popup is closed, you can generate a new client secret from the "Credentials" page.

---

## 4. MDF System Integration

1. Log into the **MDF Admin Panel**.
2. Navigate to: **Settings** → **OAuth Login Settings**.
3. Fill in the retrieved credentials as needed:
    - **Microsoft OAuth**: Enter Client ID and Client Secret.
    - **GitHub OAuth**: Enter Client ID and Client Secret.
    - **Google OAuth**: Enter Client ID and Client Secret (overseas network environments only).
4. Click **Save**.
5. Return to the login page and click the corresponding third-party icon to verify the redirect and login flow works correctly.

---

## 5. Notes & Troubleshooting

| Item                    | Description                                                                                                           |
| :---------------------- | :-------------------------------------------------------------------------------------------------------------------- |
| **Network Connectivity**| Google OAuth requires the server to be able to access `accounts.google.com`; GitHub and Microsoft usually have no special network requirements. |
| **Callback URL Consistency** | The Redirect URI configured on the third-party platform must match the address entered in the MDF admin panel exactly (including case, `http`/`https`, trailing slashes). |
| **Insufficient Permissions** | If login reports a permission error, check whether the API permission lists in Microsoft/GitHub/Google dashboards have been granted as required. |
| **Secret Leakage**      | If a Client Secret is suspected of being leaked, immediately delete the old secret on the corresponding platform and generate a new one. |

</details>
