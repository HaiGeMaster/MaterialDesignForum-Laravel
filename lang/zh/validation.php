<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 验证消息 - 中文
    |--------------------------------------------------------------------------
    |
    | 以下消息由 Laravel 验证器返回。可按需在 "custom" 中为特定字段
    | 与规则组合定制消息，或在 "attributes" 中定义字段的中文名。
    |
    */

    'accepted' => '必须接受 :attribute。',
    'accepted_if' => '当 :other 为 :value 时必须接受 :attribute。',
    'active_url' => ':attribute 必须是有效的 URL。',
    'after' => ':attribute 必须是 :date 之后的日期。',
    'after_or_equal' => ':attribute 必须是 :date 之后或同一天的日期。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、数字、短横线和下划线。',
    'alpha_num' => ':attribute 只能包含字母和数字。',
    'array' => ':attribute 必须是数组。',
    'ascii' => ':attribute 只能包含单字节字母数字字符和符号。',
    'before' => ':attribute 必须是 :date 之前的日期。',
    'before_or_equal' => ':attribute 必须是 :date 之前或同一天的日期。',
    'between' => [
        'array' => ':attribute 必须有 :min - :max 个元素。',
        'file' => ':attribute 必须介于 :min - :max KB 之间。',
        'numeric' => ':attribute 必须介于 :min - :max 之间。',
        'string' => ':attribute 必须介于 :min - :max 个字符之间。',
    ],
    'boolean' => ':attribute 必须为 true 或 false。',
    'can' => ':attribute 字段包含未授权的值。',
    'confirmed' => ':attribute 两次输入不一致。',
    'current_password' => '密码错误。',
    'date' => ':attribute 必须是有效的日期。',
    'date_equals' => ':attribute 必须等于 :date。',
    'date_format' => ':attribute 必须符合格式 :format。',
    'decimal' => ':attribute 必须有 :decimal 位小数。',
    'declined' => '必须拒绝 :attribute。',
    'declined_if' => '当 :other 为 :value 时必须拒绝 :attribute。',
    'different' => ':attribute 和 :other 必须不同。',
    'digits' => ':attribute 必须是 :digits 位数字。',
    'digits_between' => ':attribute 必须是 :min - :max 位数字。',
    'dimensions' => ':attribute 图片尺寸不正确。',
    'distinct' => ':attribute 字段有重复值。',
    'doesnt_end_with' => ':attribute 不能以 :value 结尾。',
    'doesnt_start_with' => ':attribute 不能以 :value 开头。',
    'email' => ':attribute 必须是有效的邮箱地址。',
    'ends_with' => ':attribute 必须以 :value 结尾。',
    'enum' => '选择的 :attribute 无效。',
    'exists' => ':attribute 不存在。',
    'extensions' => ':attribute 文件必须具有以下扩展名之一：:values。',
    'file' => ':attribute 必须是文件。',
    'filled' => ':attribute 不能为空。',
    'gt' => [
        'array' => ':attribute 必须多于 :value 个元素。',
        'file' => ':attribute 必须大于 :value KB。',
        'numeric' => ':attribute 必须大于 :value。',
        'string' => ':attribute 必须多于 :value 个字符。',
    ],
    'gte' => [
        'array' => ':attribute 必须至少有 :value 个元素。',
        'file' => ':attribute 必须大于等于 :value KB。',
        'numeric' => ':attribute 必须大于等于 :value。',
        'string' => ':attribute 必须至少有 :value 个字符。',
    ],
    'hex_color' => ':attribute 必须是有效的十六进制颜色。',
    'image' => ':attribute 必须是图片。',
    'in' => '选择的 :attribute 无效。',
    'in_array' => ':attribute 必须在 :other 中。',
    'integer' => ':attribute 必须是整数。',
    'ip' => ':attribute 必须是有效的 IP 地址。',
    'ipv4' => ':attribute 必须是有效的 IPv4 地址。',
    'ipv6' => ':attribute 必须是有效的 IPv6 地址。',
    'json' => ':attribute 必须是有效的 JSON 字符串。',
    'lowercase' => ':attribute 必须为小写。',
    'lt' => [
        'array' => ':attribute 必须少于 :value 个元素。',
        'file' => ':attribute 必须小于 :value KB。',
        'numeric' => ':attribute 必须小于 :value。',
        'string' => ':attribute 必须少于 :value 个字符。',
    ],
    'lte' => [
        'array' => ':attribute 必须至多有 :value 个元素。',
        'file' => ':attribute 必须小于等于 :value KB。',
        'numeric' => ':attribute 必须小于等于 :value。',
        'string' => ':attribute 必须至多有 :value 个字符。',
    ],
    'mac_address' => ':attribute 必须是有效的 MAC 地址。',
    'max' => [
        'array' => ':attribute 至多能有 :max 个元素。',
        'file' => ':attribute 不能大于 :max KB。',
        'numeric' => ':attribute 不能大于 :max。',
        'string' => ':attribute 不能多于 :max 个字符。',
    ],
    'max_digits' => ':attribute 不能多于 :max 位数字。',
    'mimes' => ':attribute 必须是 :values 类型的文件。',
    'mimetypes' => ':attribute 必须是 :values 类型的文件。',
    'min' => [
        'array' => ':attribute 至少要有 :min 个元素。',
        'file' => ':attribute 不能小于 :min KB。',
        'numeric' => ':attribute 不能小于 :min。',
        'string' => ':attribute 至少要有 :min 个字符。',
    ],
    'min_digits' => ':attribute 至少要有 :min 位数字。',
    'missing' => '必须缺少 :attribute 字段。',
    'missing_if' => '当 :other 为 :value 时必须缺少 :attribute 字段。',
    'missing_unless' => '除非 :other 为 :value，否则必须缺少 :attribute 字段。',
    'missing_with' => '当存在 :values 时必须缺少 :attribute 字段。',
    'missing_with_all' => '当同时存在 :values 时必须缺少 :attribute 字段。',
    'multiple_of' => ':attribute 必须是 :value 的倍数。',
    'not_in' => '选择的 :attribute 无效。',
    'not_regex' => ':attribute 格式无效。',
    'numeric' => ':attribute 必须是数字。',
    'password' => [
        'letters' => ':attribute 至少包含一个字母。',
        'mixed' => ':attribute 至少包含一个大写字母和一个小写字母。',
        'numbers' => ':attribute 至少包含一个数字。',
        'symbols' => ':attribute 至少包含一个符号。',
        'uncompromised' => '给定的 :attribute 已在数据泄露中出现，请更换。',
    ],
    'present' => '必须存在 :attribute 字段。',
    'present_if' => '当 :other 为 :value 时必须存在 :attribute 字段。',
    'present_unless' => '除非 :other 为 :value，否则必须存在 :attribute 字段。',
    'present_with' => '当存在 :values 时必须存在 :attribute 字段。',
    'present_with_all' => '当同时存在 :values 时必须存在 :attribute 字段。',
    'prohibited' => ':attribute 字段被禁止。',
    'prohibited_if' => '当 :other 为 :value 时禁止 :attribute 字段。',
    'prohibited_unless' => '除非 :other 为 :values，否则禁止 :attribute 字段。',
    'prohibits' => ':attribute 字段禁止出现 :other。',
    'regex' => ':attribute 格式无效。',
    'required' => '必须填写 :attribute。',
    'required_array_keys' => ':attribute 必须包含以下键：:values。',
    'required_if' => '当 :other 为 :value 时必须填写 :attribute。',
    'required_if_accepted' => '当接受 :other 时必须填写 :attribute。',
    'required_unless' => '除非 :other 为 :values，否则必须填写 :attribute。',
    'required_with' => '当存在 :values 时必须填写 :attribute。',
    'required_with_all' => '当同时存在 :values 时必须填写 :attribute。',
    'required_without' => '当缺少 :values 时必须填写 :attribute。',
    'required_without_all' => '当 :values 都不存在时必须填写 :attribute。',
    'same' => ':attribute 和 :other 必须相同。',
    'size' => [
        'array' => ':attribute 必须是 :size 个元素。',
        'file' => ':attribute 必须是 :size KB。',
        'numeric' => ':attribute 必须是 :size。',
        'string' => ':attribute 必须是 :size 个字符。',
    ],
    'starts_with' => ':attribute 必须以 :value 开头。',
    'string' => ':attribute 必须是字符串。',
    'timezone' => ':attribute 必须是有效的时区。',
    'ulid' => ':attribute 必须是有效的 ULID。',
    'unique' => ':attribute 已被占用。',
    'uppercase' => ':attribute 必须为大写。',
    'url' => ':attribute 必须是有效的 URL。',
    'uuid' => ':attribute 必须是有效的 UUID。',

    /*
    |--------------------------------------------------------------------------
    | 自定义验证消息
    |--------------------------------------------------------------------------
    |
    | 在此可为特定字段与规则组合定制消息。
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => '自定义消息',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 字段中文名
    |--------------------------------------------------------------------------
    |
    | 在此定义字段的友好名称，验证消息中的 :attribute 将替换为对应值。
    |
    */

    'attributes' => [
        'email' => '邮箱',
        'password' => '密码',
        'old_password' => '旧密码',
        'email_code' => '验证码',
        'title' => '标题',
        'content_markdown' => '内容',
        'username' => '用户名',
    ],

];
