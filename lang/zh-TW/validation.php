<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute 必須被接受。',
    'accepted_if' => '當 :other 為 :value 時，:attribute 必須被接受。',
    'active_url' => ':attribute 必須是有效的 URL。',
    'after' => ':attribute 必須是 :date 之後的日期。',
    'after_or_equal' => ':attribute 必須是 :date 當日或之後的日期。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、數字、連字號和底線。',
    'alpha_num' => ':attribute 只能包含字母和數字。',
    'array' => ':attribute 必須是陣列。',
    'ascii' => ':attribute 只能包含單一位元組的字母、數字和符號。',
    'before' => ':attribute 必須是 :date 之前的日期。',
    'before_or_equal' => ':attribute 必須是 :date 當日或之前的日期。',
    'between' => [
        'array' => ':attribute 必須包含 :min 到 :max 個項目。',
        'file' => ':attribute 大小必須在 :min 到 :max KB 之間。',
        'numeric' => ':attribute 必須在 :min 到 :max 之間。',
        'string' => ':attribute 長度必須在 :min 到 :max 個字元之間。',
    ],
    'boolean' => ':attribute 只能是 true 或 false。',
    'can' => ':attribute 包含未授權的值。',
    'confirmed' => ':attribute 確認不符。',
    'current_password' => '密碼不正確。',
    'date' => ':attribute 必須是有效的日期。',
    'date_equals' => ':attribute 必須是與 :date 相同的日期。',
    'date_format' => ':attribute 必須符合 :format 格式。',
    'decimal' => ':attribute 必須保留 :decimal 位小數。',
    'declined' => ':attribute 必須被拒絕。',
    'declined_if' => '當 :other 為 :value 時，:attribute 必須被拒絕。',
    'different' => ':attribute 與 :other 必須不同。',
    'digits' => ':attribute 必須是 :digits 位數字。',
    'digits_between' => ':attribute 必須是 :min 到 :max 位數字。',
    'dimensions' => ':attribute 的圖片尺寸無效。',
    'distinct' => ':attribute 存在重複的值。',
    'doesnt_end_with' => ':attribute 不能以下列任一值結尾：:values。',
    'doesnt_start_with' => ':attribute 不能以下列任一值開頭：:values。',
    'email' => ':attribute 必須是有效的電子郵件地址。',
    'ends_with' => ':attribute 必須以下列任一值結尾：:values。',
    'enum' => '所選的 :attribute 無效。',
    'exists' => '所選的 :attribute 無效。',
    'extensions' => ':attribute 必須是以下類型的檔案：:values。',
    'file' => ':attribute 必須是檔案。',
    'filled' => ':attribute 必須有值。',
    'gt' => [
        'array' => ':attribute 的項目數必須多於 :value 個。',
        'file' => ':attribute 大小必須大於 :value KB。',
        'numeric' => ':attribute 必須大於 :value。',
        'string' => ':attribute 長度必須大於 :value 個字元。',
    ],
    'gte' => [
        'array' => ':attribute 的項目數不得少於 :value 個。',
        'file' => ':attribute 大小必須大於或等於 :value KB。',
        'numeric' => ':attribute 必須大於或等於 :value。',
        'string' => ':attribute 長度不得少於 :value 個字元。',
    ],
    'hex_color' => ':attribute 必須是有效的十六進位顏色值。',
    'image' => ':attribute 必須是圖片。',
    'in' => '所選的 :attribute 無效。',
    'in_array' => ':attribute 必須存在於 :other 中。',
    'integer' => ':attribute 必須是整數。',
    'ip' => ':attribute 必須是有效的 IP 位址。',
    'ipv4' => ':attribute 必須是有效的 IPv4 位址。',
    'ipv6' => ':attribute 必須是有效的 IPv6 位址。',
    'json' => ':attribute 必須是有效的 JSON 字串。',
    'lowercase' => ':attribute 必須為小寫。',
    'lt' => [
        'array' => ':attribute 的項目數必須少於 :value 個。',
        'file' => ':attribute 大小必須小於 :value KB。',
        'numeric' => ':attribute 必須小於 :value。',
        'string' => ':attribute 長度必須小於 :value 個字元。',
    ],
    'lte' => [
        'array' => ':attribute 的項目數不得超過 :value 個。',
        'file' => ':attribute 大小必須小於或等於 :value KB。',
        'numeric' => ':attribute 必須小於或等於 :value。',
        'string' => ':attribute 長度不得超過 :value 個字元。',
    ],
    'mac_address' => ':attribute 必須是有效的 MAC 位址。',
    'max' => [
        'array' => ':attribute 的項目數不得超過 :max 個。',
        'file' => ':attribute 大小不得超過 :max KB。',
        'numeric' => ':attribute 不得大於 :max。',
        'string' => ':attribute 長度不得超過 :max 個字元。',
    ],
    'max_digits' => ':attribute 的位數不得超過 :max 位。',
    'mimes' => ':attribute 必須是以下類型的檔案：:values。',
    'mimetypes' => ':attribute 必須是以下類型的檔案：:values。',
    'min' => [
        'array' => ':attribute 的項目數不得少於 :min 個。',
        'file' => ':attribute 大小不得小於 :min KB。',
        'numeric' => ':attribute 不得小於 :min。',
        'string' => ':attribute 長度不得少於 :min 個字元。',
    ],
    'min_digits' => ':attribute 的位數不得少於 :min 位。',
    'missing' => ':attribute 欄位必須留空。',
    'missing_if' => '當 :other 為 :value 時，:attribute 欄位必須留空。',
    'missing_unless' => '除非 :other 為 :value，否則 :attribute 欄位必須留空。',
    'missing_with' => '當 :values 存在時，:attribute 欄位必須留空。',
    'missing_with_all' => '當 :values 全部存在時，:attribute 欄位必須留空。',
    'multiple_of' => ':attribute 必須是 :value 的倍數。',
    'not_in' => '所選的 :attribute 無效。',
    'not_regex' => ':attribute 的格式無效。',
    'numeric' => ':attribute 必須是數字。',
    'password' => [
        'letters' => ':attribute 必須包含至少一個字母。',
        'mixed' => ':attribute 必須包含至少一個大寫字母和一個小寫字母。',
        'numbers' => ':attribute 必須包含至少一個數字。',
        'symbols' => ':attribute 必須包含至少一個符號。',
        'uncompromised' => '該 :attribute 曾出現在資料外洩中，請選擇其他 :attribute。',
    ],
    'present' => ':attribute 欄位必須存在。',
    'present_if' => '當 :other 為 :value 時，:attribute 欄位必須存在。',
    'present_unless' => '除非 :other 為 :value，否則 :attribute 欄位必須存在。',
    'present_with' => '當 :values 存在時，:attribute 欄位必須存在。',
    'present_with_all' => '當 :values 全部存在時，:attribute 欄位必須存在。',
    'prohibited' => ':attribute 欄位被禁止。',
    'prohibited_if' => '當 :other 為 :value 時，:attribute 欄位被禁止。',
    'prohibited_unless' => '除非 :other 在 :values 中，否則 :attribute 欄位被禁止。',
    'prohibits' => ':attribute 欄位存在時，:other 不得存在。',
    'regex' => ':attribute 的格式無效。',
    'required' => ':attribute 欄位為必填。',
    'required_array_keys' => ':attribute 欄位必須包含：:values。',
    'required_if' => '當 :other 為 :value 時，:attribute 欄位為必填。',
    'required_if_accepted' => '當 :other 被接受時，:attribute 欄位為必填。',
    'required_unless' => '除非 :other 在 :values 中，否則 :attribute 欄位為必填。',
    'required_with' => '當 :values 存在時，:attribute 欄位為必填。',
    'required_with_all' => '當 :values 全部存在時，:attribute 欄位為必填。',
    'required_without' => '當 :values 不存在時，:attribute 欄位為必填。',
    'required_without_all' => '當 :values 全部不存在時，:attribute 欄位為必填。',
    'same' => ':attribute 與 :other 必須一致。',
    'size' => [
        'array' => ':attribute 必須包含 :size 個項目。',
        'file' => ':attribute 大小必須為 :size KB。',
        'numeric' => ':attribute 必須為 :size。',
        'string' => ':attribute 長度必須為 :size 個字元。',
    ],
    'starts_with' => ':attribute 必須以下列任一值開頭：:values。',
    'string' => ':attribute 必須是字串。',
    'timezone' => ':attribute 必須是有效的時區。',
    'ulid' => ':attribute 必須是有效的 ULID。',
    'unique' => ':attribute 已被使用。',
    'uppercase' => ':attribute 必須為大寫。',
    'url' => ':attribute 必須是有效的 URL。',
    'uuid' => ':attribute 必須是有效的 UUID。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines.
    |
    */

    '属性名称' => [
        '规则名称' => '自订讯息',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
