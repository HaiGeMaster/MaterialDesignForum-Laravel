<?php

return [
    'effective_date' => env('COOKIE_POLICY_EFFECTIVE_DATE', '【请填写生效日期】'),
    'updated_date' => env('COOKIE_POLICY_UPDATED_DATE', '【请填写最近更新日期】'),
    'policy_version' => env('COOKIE_POLICY_VERSION', '1.0'),
    'company_name' => env('COOKIE_POLICY_COMPANY_NAME', config('app.name')),
    'website_domain' => env('COOKIE_POLICY_WEBSITE_DOMAIN', config('app.url')),
    'registered_address' => env('COOKIE_POLICY_REGISTERED_ADDRESS', '【请填写注册地址】'),
    'privacy_email' => env('COOKIE_POLICY_PRIVACY_EMAIL', '【请填写隐私联系邮箱】'),
    'cloudflare_clearance_duration' => env('COOKIE_POLICY_CF_CLEARANCE_DURATION', '30 分钟至 1 年（取决于后台配置）'),
    'ui_locale_duration' => env('COOKIE_POLICY_UI_LOCALE_DURATION', '【请填写实际存续期】'),
    'ui_theme_duration' => env('COOKIE_POLICY_UI_THEME_DURATION', '【请填写实际存续期】'),
    'clarity_clck_duration' => env('COOKIE_POLICY_CLARITY_CLCK_DURATION', '1 年'),
    'clarity_clsk_duration' => env('COOKIE_POLICY_CLARITY_CLSK_DURATION', '1 天'),
    'muid_duration' => env('COOKIE_POLICY_MUID_DURATION', '13 个月（390 天）'),
    'eu_uk_legal_basis_note' => env('COOKIE_POLICY_EU_UK_LEGAL_BASIS_NOTE', ''),
    'eu_uk_transfer_note' => env('COOKIE_POLICY_EU_UK_TRANSFER_NOTE', ''),
    'maximum_cookie_retention' => env('COOKIE_POLICY_MAXIMUM_RETENTION', '13 个月'),
    'consent_record_retention' => env('COOKIE_POLICY_CONSENT_RETENTION', '12 个月'),
    'contact_phone' => env('COOKIE_POLICY_CONTACT_PHONE', '【如有，请填写联系电话】'),
    'response_period' => env('COOKIE_POLICY_RESPONSE_PERIOD', '15 个工作日'),
    'eu_uk_complaint_note' => env('COOKIE_POLICY_EU_UK_COMPLAINT_NOTE', ''),
];
