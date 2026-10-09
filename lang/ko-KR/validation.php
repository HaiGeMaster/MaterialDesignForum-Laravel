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

    'accepted' => ':attribute을(를) 동의해야 합니다.',
    'accepted_if' => ':other이(가) :value일 때 :attribute을(를) 동의해야 합니다.',
    'active_url' => ':attribute은(는) 유효한 URL이어야 합니다.',
    'after' => ':attribute은(는) :date 이후 날짜여야 합니다.',
    'after_or_equal' => ':attribute은(는) :date 이후 또는 같은 날짜여야 합니다.',
    'alpha' => ':attribute은(는) 문자만 입력할 수 있습니다.',
    'alpha_dash' => ':attribute은(는) 문자, 숫자, 하이픈, 밑줄만 입력할 수 있습니다.',
    'alpha_num' => ':attribute은(는) 문자와 숫자만 입력할 수 있습니다.',
    'array' => ':attribute은(는) 배열이어야 합니다.',
    'ascii' => ':attribute은(는) 싱글바이트 영숫자와 기호 문자만 입력할 수 있습니다.',
    'before' => ':attribute은(는) :date 이전 날짜여야 합니다.',
    'before_or_equal' => ':attribute은(는) :date 이전 또는 같은 날짜여야 합니다.',
    'between' => [
        'array' => ':attribute은(는) :min개에서 :max개 사이여야 합니다.',
        'file' => ':attribute은(는) :min KB에서 :max KB 사이여야 합니다.',
        'numeric' => ':attribute은(는) :min에서 :max 사이여야 합니다.',
        'string' => ':attribute은(는) :min자에서 :max자 사이여야 합니다.',
    ],
    'boolean' => ':attribute 필드는 true 또는 false여야 합니다.',
    'can' => ':attribute 필드에 허용되지 않은 값이 있습니다.',
    'confirmed' => ':attribute 확인이 일치하지 않습니다.',
    'current_password' => '비밀번호가 올바르지 않습니다.',
    'date' => ':attribute은(는) 유효한 날짜여야 합니다.',
    'date_equals' => ':attribute은(는) :date와 같은 날짜여야 합니다.',
    'date_format' => ':attribute은(는) :format 형식과 일치해야 합니다.',
    'decimal' => ':attribute은(는) 소수점 :decimal 자리여야 합니다.',
    'declined' => ':attribute은(는) 거부되어야 합니다.',
    'declined_if' => ':other이(가) :value일 때 :attribute은(는) 거부되어야 합니다.',
    'different' => ':attribute과(와) :other은(는) 달라야 합니다.',
    'digits' => ':attribute은(는) :digits 자리여야 합니다.',
    'digits_between' => ':attribute은(는) :min자리에서 :max자리 사이여야 합니다.',
    'dimensions' => ':attribute의 이미지 크기가 올바르지 않습니다.',
    'distinct' => ':attribute 필드에 중복된 값이 있습니다.',
    'doesnt_end_with' => ':attribute은(는) 다음으로 끝날 수 없습니다: :values',
    'doesnt_start_with' => ':attribute은(는) 다음으로 시작할 수 없습니다: :values',
    'email' => ':attribute은(는) 유효한 이메일 주소여야 합니다.',
    'ends_with' => ':attribute은(는) 다음 중 하나로 끝나야 합니다: :values',
    'enum' => '선택한 :attribute이(가) 올바르지 않습니다.',
    'exists' => '선택한 :attribute이(가) 올바르지 않습니다.',
    'extensions' => ':attribute 필드는 다음 형식의 파일이어야 합니다: :values',
    'file' => ':attribute은(는) 파일이어야 합니다.',
    'filled' => ':attribute 필드에 값이 있어야 합니다.',
    'gt' => [
        'array' => ':attribute은(는) :value개보다 많아야 합니다.',
        'file' => ':attribute은(는) :value KB보다 커야 합니다.',
        'numeric' => ':attribute은(는) :value보다 커야 합니다.',
        'string' => ':attribute은(는) :value자보다 길어야 합니다.',
    ],
    'gte' => [
        'array' => ':attribute은(는) 최소 :value개여야 합니다.',
        'file' => ':attribute은(는) :value KB 이상이어야 합니다.',
        'numeric' => ':attribute은(는) :value 이상이어야 합니다.',
        'string' => ':attribute은(는) 최소 :value자여야 합니다.',
    ],
    'hex_color' => ':attribute 필드는 유효한 16진수 색상이어야 합니다.',
    'image' => ':attribute은(는) 이미지여야 합니다.',
    'in' => '선택한 :attribute이(가) 올바르지 않습니다.',
    'in_array' => ':attribute 필드는 :other에 존재해야 합니다.',
    'integer' => ':attribute은(는) 정수여야 합니다.',
    'ip' => ':attribute은(는) 유효한 IP 주소여야 합니다.',
    'ipv4' => ':attribute은(는) 유효한 IPv4 주소여야 합니다.',
    'ipv6' => ':attribute은(는) 유효한 IPv6 주소여야 합니다.',
    'json' => ':attribute은(는) 유효한 JSON 문자열이어야 합니다.',
    'lowercase' => ':attribute은(는) 소문자여야 합니다.',
    'lt' => [
        'array' => ':attribute은(는) :value개보다 적어야 합니다.',
        'file' => ':attribute은(는) :value KB보다 작아야 합니다.',
        'numeric' => ':attribute은(는) :value보다 작아야 합니다.',
        'string' => ':attribute은(는) :value자보다 짧아야 합니다.',
    ],
    'lte' => [
        'array' => ':attribute은(는) :value개를 초과할 수 없습니다.',
        'file' => ':attribute은(는) :value KB 이하여야 합니다.',
        'numeric' => ':attribute은(는) :value 이하여야 합니다.',
        'string' => ':attribute은(는) :value자를 초과할 수 없습니다.',
    ],
    'mac_address' => ':attribute은(는) 유효한 MAC 주소여야 합니다.',
    'max' => [
        'array' => ':attribute은(는) :max개를 초과할 수 없습니다.',
        'file' => ':attribute은(는) :max KB를 초과할 수 없습니다.',
        'numeric' => ':attribute은(는) :max를 초과할 수 없습니다.',
        'string' => ':attribute은(는) :max자를 초과할 수 없습니다.',
    ],
    'max_digits' => ':attribute은(는) :max자리를 초과할 수 없습니다.',
    'mimes' => ':attribute은(는) 다음 형식의 파일이어야 합니다: :values',
    'mimetypes' => ':attribute은(는) 다음 형식의 파일이어야 합니다: :values',
    'min' => [
        'array' => ':attribute은(는) 최소 :min개여야 합니다.',
        'file' => ':attribute은(는) 최소 :min KB여야 합니다.',
        'numeric' => ':attribute은(는) 최소 :min이어야 합니다.',
        'string' => ':attribute은(는) 최소 :min자여야 합니다.',
    ],
    'min_digits' => ':attribute은(는) 최소 :min자리여야 합니다.',
    'missing' => ':attribute 필드는 비어 있어야 합니다.',
    'missing_if' => ':other이(가) :value일 때 :attribute 필드는 비어 있어야 합니다.',
    'missing_unless' => ':other이(가) :value가 아닌 경우 :attribute 필드는 비어 있어야 합니다.',
    'missing_with' => ':values이(가) 있으면 :attribute 필드는 비어 있어야 합니다.',
    'missing_with_all' => ':values이(가) 모두 있으면 :attribute 필드는 비어 있어야 합니다.',
    'multiple_of' => ':attribute은(는) :value의 배수여야 합니다.',
    'not_in' => '선택한 :attribute이(가) 올바르지 않습니다.',
    'not_regex' => ':attribute 형식이 올바르지 않습니다.',
    'numeric' => ':attribute은(는) 숫자여야 합니다.',
    'password' => [
        'letters' => ':attribute은(는) 최소 한 개의 문자를 포함해야 합니다.',
        'mixed' => ':attribute은(는) 최소 한 개의 대문자와 한 개의 소문자를 포함해야 합니다.',
        'numbers' => ':attribute은(는) 최소 한 개의 숫자를 포함해야 합니다.',
        'symbols' => ':attribute은(는) 최소 한 개의 기호를 포함해야 합니다.',
        'uncompromised' => '입력한 :attribute은(는) 데이터 유출에 포함된 값입니다. 다른 :attribute을(를) 사용하세요.',
    ],
    'present' => ':attribute 필드가 있어야 합니다.',
    'present_if' => ':other이(가) :value일 때 :attribute 필드가 있어야 합니다.',
    'present_unless' => ':other이(가) :value가 아닌 경우 :attribute 필드가 있어야 합니다.',
    'present_with' => ':values이(가) 있으면 :attribute 필드가 있어야 합니다.',
    'present_with_all' => ':values이(가) 모두 있으면 :attribute 필드가 있어야 합니다.',
    'prohibited' => ':attribute 필드는 사용할 수 없습니다.',
    'prohibited_if' => ':other이(가) :value일 때 :attribute 필드는 사용할 수 없습니다.',
    'prohibited_unless' => ':other이(가) :values에 없으면 :attribute 필드는 사용할 수 없습니다.',
    'prohibits' => ':attribute 필드가 있으면 :other은(는) 사용할 수 없습니다.',
    'regex' => ':attribute 형식이 올바르지 않습니다.',
    'required' => ':attribute 필드는 필수입니다.',
    'required_array_keys' => ':attribute 필드는 다음을 포함해야 합니다: :values',
    'required_if' => ':other이(가) :value일 때 :attribute 필드는 필수입니다.',
    'required_if_accepted' => ':other이(가) 동의된 경우 :attribute 필드는 필수입니다.',
    'required_unless' => ':other이(가) :values에 없으면 :attribute 필드는 필수입니다.',
    'required_with' => ':values이(가) 있으면 :attribute 필드는 필수입니다.',
    'required_with_all' => ':values이(가) 모두 있으면 :attribute 필드는 필수입니다.',
    'required_without' => ':values이(가) 없으면 :attribute 필드는 필수입니다.',
    'required_without_all' => ':values이(가) 모두 없으면 :attribute 필드는 필수입니다.',
    'same' => ':attribute과(와) :other은(는) 일치해야 합니다.',
    'size' => [
        'array' => ':attribute은(는) :size개여야 합니다.',
        'file' => ':attribute은(는) :size KB여야 합니다.',
        'numeric' => ':attribute은(는) :size여야 합니다.',
        'string' => ':attribute은(는) :size자여야 합니다.',
    ],
    'starts_with' => ':attribute은(는) 다음 중 하나로 시작해야 합니다: :values',
    'string' => ':attribute은(는) 문자열이어야 합니다.',
    'timezone' => ':attribute은(는) 유효한 시간대여야 합니다.',
    'ulid' => ':attribute은(는) 유효한 ULID여야 합니다.',
    'unique' => ':attribute은(는) 이미 사용 중입니다.',
    'uppercase' => ':attribute은(는) 대문자여야 합니다.',
    'url' => ':attribute은(는) 유효한 URL이어야 합니다.',
    'uuid' => ':attribute은(는) 유효한 UUID여야 합니다.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines.
    |
    */

    '속성-이름' => [
        '규칙-이름' => '사용자-정의-메시지',
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
