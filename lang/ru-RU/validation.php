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

    'accepted' => ':attribute должен быть принят.',
    'accepted_if' => ':attribute должен быть принят, когда :other равно :value.',
    'active_url' => ':attribute должен быть действительным URL.',
    'after' => ':attribute должен быть датой после :date.',
    'after_or_equal' => ':attribute должен быть датой после или равной :date.',
    'alpha' => ':attribute может содержать только буквы.',
    'alpha_dash' => ':attribute может содержать только буквы, цифры, дефисы и подчёркивания.',
    'alpha_num' => ':attribute может содержать только буквы и цифры.',
    'array' => ':attribute должен быть массивом.',
    'ascii' => ':attribute может содержать только однобайтовые буквы, цифры и символы.',
    'before' => ':attribute должен быть датой до :date.',
    'before_or_equal' => ':attribute должен быть датой до или равной :date.',
    'between' => [
        'array' => 'Количество элементов в :attribute должно быть от :min до :max.',
        'file' => 'Размер файла :attribute должен быть от :min до :max килобайт.',
        'numeric' => ':attribute должно быть от :min до :max.',
        'string' => 'Длина :attribute должна быть от :min до :max символов.',
    ],
    'boolean' => 'Поле :attribute должно быть true или false.',
    'can' => 'Поле :attribute содержит недопустимое значение.',
    'confirmed' => 'Подтверждение :attribute не совпадает.',
    'current_password' => 'Неверный пароль.',
    'date' => ':attribute должен быть корректной датой.',
    'date_equals' => ':attribute должен быть датой, равной :date.',
    'date_format' => ':attribute должен соответствовать формату :format.',
    'decimal' => ':attribute должен содержать :decimal знаков после запятой.',
    'declined' => ':attribute должен быть отклонён.',
    'declined_if' => ':attribute должен быть отклонён, когда :other равно :value.',
    'different' => ':attribute и :other должны различаться.',
    'digits' => ':attribute должен содержать :digits цифр.',
    'digits_between' => ':attribute должен содержать от :min до :max цифр.',
    'dimensions' => ':attribute имеет недопустимые размеры изображения.',
    'distinct' => 'Поле :attribute содержит повторяющееся значение.',
    'doesnt_end_with' => ':attribute не должен заканчиваться одним из следующих: :values.',
    'doesnt_start_with' => ':attribute не должен начинаться с одного из следующих: :values.',
    'email' => ':attribute должен быть корректным адресом электронной почты.',
    'ends_with' => ':attribute должен заканчиваться одним из следующих: :values.',
    'enum' => 'Выбранное значение :attribute некорректно.',
    'exists' => 'Выбранное значение :attribute некорректно.',
    'extensions' => 'Поле :attribute должно быть файлом одного из типов: :values.',
    'file' => ':attribute должен быть файлом.',
    'filled' => 'Поле :attribute должно иметь значение.',
    'gt' => [
        'array' => 'Количество элементов в :attribute должно быть больше :value.',
        'file' => 'Размер файла :attribute должен быть больше :value килобайт.',
        'numeric' => ':attribute должно быть больше :value.',
        'string' => 'Длина :attribute должна быть больше :value символов.',
    ],
    'gte' => [
        'array' => 'Количество элементов в :attribute должно быть :value или больше.',
        'file' => 'Размер файла :attribute должен быть не менее :value килобайт.',
        'numeric' => ':attribute должно быть больше или равно :value.',
        'string' => 'Длина :attribute должна быть не менее :value символов.',
    ],
    'hex_color' => 'Поле :attribute должно быть корректным шестнадцатеричным цветом.',
    'image' => ':attribute должен быть изображением.',
    'in' => 'Выбранное значение :attribute некорректно.',
    'in_array' => 'Поле :attribute должно присутствовать в :other.',
    'integer' => ':attribute должен быть целым числом.',
    'ip' => ':attribute должен быть корректным IP-адресом.',
    'ipv4' => ':attribute должен быть корректным IPv4-адресом.',
    'ipv6' => ':attribute должен быть корректным IPv6-адресом.',
    'json' => ':attribute должен быть корректной JSON-строкой.',
    'lowercase' => ':attribute должен быть в нижнем регистре.',
    'lt' => [
        'array' => 'Количество элементов в :attribute должно быть меньше :value.',
        'file' => 'Размер файла :attribute должен быть меньше :value килобайт.',
        'numeric' => ':attribute должно быть меньше :value.',
        'string' => 'Длина :attribute должна быть меньше :value символов.',
    ],
    'lte' => [
        'array' => 'Количество элементов в :attribute не должно превышать :value.',
        'file' => 'Размер файла :attribute должен быть не более :value килобайт.',
        'numeric' => ':attribute должно быть меньше или равно :value.',
        'string' => 'Длина :attribute не должна превышать :value символов.',
    ],
    'mac_address' => ':attribute должен быть корректным MAC-адресом.',
    'max' => [
        'array' => 'Количество элементов в :attribute не должно превышать :max.',
        'file' => 'Размер файла :attribute не должен превышать :max килобайт.',
        'numeric' => ':attribute не должно быть больше :max.',
        'string' => 'Длина :attribute не должна превышать :max символов.',
    ],
    'max_digits' => 'Количество цифр в :attribute не должно превышать :max.',
    'mimes' => ':attribute должен быть файлом одного из типов: :values.',
    'mimetypes' => ':attribute должен быть файлом одного из типов: :values.',
    'min' => [
        'array' => 'Количество элементов в :attribute должно быть не менее :min.',
        'file' => 'Размер файла :attribute должен быть не менее :min килобайт.',
        'numeric' => ':attribute должно быть не менее :min.',
        'string' => 'Длина :attribute должна быть не менее :min символов.',
    ],
    'min_digits' => 'Количество цифр в :attribute должно быть не менее :min.',
    'missing' => 'Поле :attribute должно отсутствовать.',
    'missing_if' => 'Поле :attribute должно отсутствовать, когда :other равно :value.',
    'missing_unless' => 'Поле :attribute должно отсутствовать, если :other не равно :value.',
    'missing_with' => 'Поле :attribute должно отсутствовать, если присутствует :values.',
    'missing_with_all' => 'Поле :attribute должно отсутствовать, если присутствуют :values.',
    'multiple_of' => ':attribute должно быть кратным :value.',
    'not_in' => 'Выбранное значение :attribute некорректно.',
    'not_regex' => 'Формат :attribute некорректен.',
    'numeric' => ':attribute должно быть числом.',
    'password' => [
        'letters' => ':attribute должен содержать хотя бы одну букву.',
        'mixed' => ':attribute должен содержать хотя бы одну заглавную и одну строчную букву.',
        'numbers' => ':attribute должен содержать хотя бы одну цифру.',
        'symbols' => ':attribute должен содержать хотя бы один символ.',
        'uncompromised' => 'Указанный :attribute обнаружен в утечке данных. Пожалуйста, выберите другой :attribute.',
    ],
    'present' => 'Поле :attribute должно присутствовать.',
    'present_if' => 'Поле :attribute должно присутствовать, когда :other равно :value.',
    'present_unless' => 'Поле :attribute должно присутствовать, если :other не равно :value.',
    'present_with' => 'Поле :attribute должно присутствовать, если присутствует :values.',
    'present_with_all' => 'Поле :attribute должно присутствовать, если присутствуют :values.',
    'prohibited' => 'Поле :attribute запрещено.',
    'prohibited_if' => 'Поле :attribute запрещено, когда :other равно :value.',
    'prohibited_unless' => 'Поле :attribute запрещено, если :other не входит в :values.',
    'prohibits' => 'Поле :attribute запрещает присутствие :other.',
    'regex' => 'Формат :attribute некорректен.',
    'required' => 'Поле :attribute обязательно для заполнения.',
    'required_array_keys' => 'Поле :attribute должно содержать: :values.',
    'required_if' => 'Поле :attribute обязательно, когда :other равно :value.',
    'required_if_accepted' => 'Поле :attribute обязательно, когда :other принято.',
    'required_unless' => 'Поле :attribute обязательно, если :other не входит в :values.',
    'required_with' => 'Поле :attribute обязательно, если присутствует :values.',
    'required_with_all' => 'Поле :attribute обязательно, если присутствуют :values.',
    'required_without' => 'Поле :attribute обязательно, если :values отсутствует.',
    'required_without_all' => 'Поле :attribute обязательно, если ни одно из :values не присутствует.',
    'same' => ':attribute и :other должны совпадать.',
    'size' => [
        'array' => 'Количество элементов в :attribute должно быть :size.',
        'file' => 'Размер файла :attribute должен быть :size килобайт.',
        'numeric' => ':attribute должно быть :size.',
        'string' => 'Длина :attribute должна быть :size символов.',
    ],
    'starts_with' => ':attribute должен начинаться с одного из следующих: :values.',
    'string' => ':attribute должно быть строкой.',
    'timezone' => ':attribute должен быть корректным часовым поясом.',
    'ulid' => ':attribute должен быть корректным ULID.',
    'unique' => 'Такое значение :attribute уже существует.',
    'uppercase' => ':attribute должен быть в верхнем регистре.',
    'url' => ':attribute должен быть корректным URL.',
    'uuid' => ':attribute должен быть корректным UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines.
    |
    */

    'имя-атрибута' => [
        'имя-правила' => 'пользовательское-сообщение',
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
