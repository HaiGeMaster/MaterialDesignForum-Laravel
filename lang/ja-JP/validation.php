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

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください。',
    'active_url' => ':attributeは有効なURLでなければなりません。',
    'after' => ':attributeは:dateより後の日付でなければなりません。',
    'after_or_equal' => ':attributeは:date以降の日付でなければなりません。',
    'alpha' => ':attributeは英字のみで入力してください。',
    'alpha_dash' => ':attributeは英字、数字、ハイフン、アンダースコアのみで入力してください。',
    'alpha_num' => ':attributeは英字と数字のみで入力してください。',
    'array' => ':attributeは配列でなければなりません。',
    'ascii' => ':attributeは半角英数字と記号のみで入力してください。',
    'before' => ':attributeは:dateより前の日付でなければなりません。',
    'before_or_equal' => ':attributeは:date以前の日付でなければなりません。',
    'between' => [
        'array' => ':attributeは:min個から:max個の項目でなければなりません。',
        'file' => ':attributeは:min KBから:max KBでなければなりません。',
        'numeric' => ':attributeは:minから:maxの範囲でなければなりません。',
        'string' => ':attributeは:min文字から:max文字でなければなりません。',
    ],
    'boolean' => ':attributeはtrueまたはfalseでなければなりません。',
    'can' => ':attributeに許可されていない値が含まれています。',
    'confirmed' => ':attributeの確認が一致しません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeは有効な日付でなければなりません。',
    'date_equals' => ':attributeは:dateと同じ日付でなければなりません。',
    'date_format' => ':attributeは:format形式でなければなりません。',
    'decimal' => ':attributeは小数:decimal桁でなければなりません。',
    'declined' => ':attributeは拒否されている必要があります。',
    'declined_if' => ':otherが:valueの場合、:attributeは拒否されている必要があります。',
    'different' => ':attributeと:otherは異なる値でなければなりません。',
    'digits' => ':attributeは:digits桁でなければなりません。',
    'digits_between' => ':attributeは:min桁から:max桁でなければなりません。',
    'dimensions' => ':attributeの画像サイズが無効です。',
    'distinct' => ':attributeに重複した値があります。',
    'doesnt_end_with' => ':attributeは次の値で終わってはいけません：:values',
    'doesnt_start_with' => ':attributeは次の値で始まってはいけません：:values',
    'email' => ':attributeは有効なメールアドレスでなければなりません。',
    'ends_with' => ':attributeは次のいずれかで終わらなければなりません：:values',
    'enum' => '選択された:attributeは無効です。',
    'exists' => '選択された:attributeは無効です。',
    'extensions' => ':attributeは次の種類のファイルでなければなりません：:values',
    'file' => ':attributeはファイルでなければなりません。',
    'filled' => ':attributeには値を入力してください。',
    'gt' => [
        'array' => ':attributeは:value個より多くの項目が必要です。',
        'file' => ':attributeは:value KBより大きくなければなりません。',
        'numeric' => ':attributeは:valueより大きくなければなりません。',
        'string' => ':attributeは:value文字より長くなければなりません。',
    ],
    'gte' => [
        'array' => ':attributeは:value個以上の項目が必要です。',
        'file' => ':attributeは:value KB以上でなければなりません。',
        'numeric' => ':attributeは:value以上でなければなりません。',
        'string' => ':attributeは:value文字以上でなければなりません。',
    ],
    'hex_color' => ':attributeは有効な16進数カラーコードでなければなりません。',
    'image' => ':attributeは画像でなければなりません。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeは:otherに存在しなければなりません。',
    'integer' => ':attributeは整数でなければなりません。',
    'ip' => ':attributeは有効なIPアドレスでなければなりません。',
    'ipv4' => ':attributeは有効なIPv4アドレスでなければなりません。',
    'ipv6' => ':attributeは有効なIPv6アドレスでなければなりません。',
    'json' => ':attributeは有効なJSON文字列でなければなりません。',
    'lowercase' => ':attributeは小文字でなければなりません。',
    'lt' => [
        'array' => ':attributeは:value個未満でなければなりません。',
        'file' => ':attributeは:value KB未満でなければなりません。',
        'numeric' => ':attributeは:value未満でなければなりません。',
        'string' => ':attributeは:value文字未満でなければなりません。',
    ],
    'lte' => [
        'array' => ':attributeは:value個以下でなければなりません。',
        'file' => ':attributeは:value KB以下でなければなりません。',
        'numeric' => ':attributeは:value以下でなければなりません。',
        'string' => ':attributeは:value文字以下でなければなりません。',
    ],
    'mac_address' => ':attributeは有効なMACアドレスでなければなりません。',
    'max' => [
        'array' => ':attributeは:max個以下の項目でなければなりません。',
        'file' => ':attributeは:max KB以下でなければなりません。',
        'numeric' => ':attributeは:max以下でなければなりません。',
        'string' => ':attributeは:max文字以下でなければなりません。',
    ],
    'max_digits' => ':attributeは:max桁以下でなければなりません。',
    'mimes' => ':attributeは次の種類のファイルでなければなりません：:values',
    'mimetypes' => ':attributeは次の種類のファイルでなければなりません：:values',
    'min' => [
        'array' => ':attributeは少なくとも:min個の項目が必要です。',
        'file' => ':attributeは少なくとも:min KBでなければなりません。',
        'numeric' => ':attributeは少なくとも:min以上でなければなりません。',
        'string' => ':attributeは少なくとも:min文字以上でなければなりません。',
    ],
    'min_digits' => ':attributeは少なくとも:min桁必要です。',
    'missing' => ':attributeは入力できません。',
    'missing_if' => ':otherが:valueの場合、:attributeは入力できません。',
    'missing_unless' => ':otherが:valueでない限り、:attributeは入力できません。',
    'missing_with' => ':valuesが存在する場合、:attributeは入力できません。',
    'missing_with_all' => ':valuesがすべて存在する場合、:attributeは入力できません。',
    'multiple_of' => ':attributeは:valueの倍数でなければなりません。',
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeの形式が無効です。',
    'numeric' => ':attributeは数値でなければなりません。',
    'password' => [
        'letters' => ':attributeには少なくとも1つの英字を含めてください。',
        'mixed' => ':attributeには少なくとも1つの大文字と1つの小文字を含めてください。',
        'numbers' => ':attributeには少なくとも1つの数字を含めてください。',
        'symbols' => ':attributeには少なくとも1つの記号を含めてください。',
        'uncompromised' => '指定された:attributeはデータ漏洩に含まれています。別の:attributeを選択してください。',
    ],
    'present' => ':attributeは入力必須です。',
    'present_if' => ':otherが:valueの場合、:attributeは入力必須です。',
    'present_unless' => ':otherが:valueでない限り、:attributeは入力必須です。',
    'present_with' => ':valuesが存在する場合、:attributeは入力必須です。',
    'present_with_all' => ':valuesがすべて存在する場合、:attributeは入力必須です。',
    'prohibited' => ':attributeは禁止されています。',
    'prohibited_if' => ':otherが:valueの場合、:attributeは禁止されています。',
    'prohibited_unless' => ':otherが:valuesに含まれない限り、:attributeは禁止されています。',
    'prohibits' => ':attributeが存在する場合、:otherは存在できません。',
    'regex' => ':attributeの形式が無効です。',
    'required' => ':attributeは必須です。',
    'required_array_keys' => ':attributeには次の値を含める必要があります：:values',
    'required_if' => ':otherが:valueの場合、:attributeは必須です。',
    'required_if_accepted' => ':otherが承認されている場合、:attributeは必須です。',
    'required_unless' => ':otherが:valuesに含まれない限り、:attributeは必須です。',
    'required_with' => ':valuesが存在する場合、:attributeは必須です。',
    'required_with_all' => ':valuesがすべて存在する場合、:attributeは必須です。',
    'required_without' => ':valuesが存在しない場合、:attributeは必須です。',
    'required_without_all' => ':valuesがいずれも存在しない場合、:attributeは必須です。',
    'same' => ':attributeと:otherは一致しなければなりません。',
    'size' => [
        'array' => ':attributeは:size個の項目でなければなりません。',
        'file' => ':attributeは:size KBでなければなりません。',
        'numeric' => ':attributeは:sizeでなければなりません。',
        'string' => ':attributeは:size文字でなければなりません。',
    ],
    'starts_with' => ':attributeは次のいずれかで始まらなければなりません：:values',
    'string' => ':attributeは文字列でなければなりません。',
    'timezone' => ':attributeは有効なタイムゾーンでなければなりません。',
    'ulid' => ':attributeは有効なULIDでなければなりません。',
    'unique' => ':attributeはすでに使用されています。',
    'uppercase' => ':attributeは大文字でなければなりません。',
    'url' => ':attributeは有効なURLでなければなりません。',
    'uuid' => ':attributeは有効なUUIDでなければなりません。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines.
    |
    */

    '属性名' => [
        'ルール名' => 'カスタムメッセージ',
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
