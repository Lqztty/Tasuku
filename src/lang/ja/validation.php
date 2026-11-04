<?php

return [

    /*
    |--------------------------------------------------------------------------
    | バリデーションのメッセージ
    |--------------------------------------------------------------------------
    |
    | Laravel 標準のルールが使う既定のメッセージ。機能ごとの Request で
    | messages() / attributes() を書いた場合は、そちらが優先される。
    |
    */

    'accepted' => ':attributeを承認してください。',
    'accepted_if' => ':otherが:valueのときは、:attributeを承認してください。',
    'active_url' => ':attributeには有効な URL を指定してください。',
    'after' => ':attributeには:dateより後の日付を指定してください。',
    'after_or_equal' => ':attributeには:date以降の日付を指定してください。',
    'alpha' => ':attributeには英字だけを使えます。',
    'alpha_dash' => ':attributeには英数字・ハイフン・アンダースコアだけを使えます。',
    'alpha_num' => ':attributeには英数字だけを使えます。',
    'any_of' => ':attributeの値が正しくありません。',
    'array' => ':attributeには配列を指定してください。',
    'array_keys' => ':attributeには次のキーだけを含めてください: :values',
    'ascii' => ':attributeには半角の英数字と記号だけを使えます。',
    'base64' => ':attributeには有効な Base64 の文字列を指定してください。',
    'before' => ':attributeには:dateより前の日付を指定してください。',
    'before_or_equal' => ':attributeには:date以前の日付を指定してください。',
    'between' => [
        'array' => ':attributeは:min〜:max個にしてください。',
        'file' => ':attributeは:min〜:max KB のファイルにしてください。',
        'numeric' => ':attributeは:min〜:maxの値にしてください。',
        'string' => ':attributeは:min〜:max文字にしてください。',
    ],
    'boolean' => ':attributeには true か false を指定してください。',
    'can' => ':attributeに許可されていない値が含まれています。',
    'confirmed' => ':attributeが確認用の値と一致しません。',
    'contains' => ':attributeに必要な値が含まれていません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeには有効な日付を指定してください。',
    'date_equals' => ':attributeには:dateと同じ日付を指定してください。',
    'date_format' => ':attributeは:formatの形式で入力してください。',
    'decimal' => ':attributeは小数点以下:decimal桁にしてください。',
    'declined' => ':attributeを拒否してください。',
    'declined_if' => ':otherが:valueのときは、:attributeを拒否してください。',
    'different' => ':attributeと:otherには異なる値を指定してください。',
    'digits' => ':attributeは:digits桁にしてください。',
    'digits_between' => ':attributeは:min〜:max桁にしてください。',
    'dimensions' => ':attributeの画像サイズが正しくありません。',
    'distinct' => ':attributeの値が重複しています。',
    'doesnt_contain' => ':attributeには次の値を含めないでください: :values',
    'doesnt_end_with' => ':attributeの末尾を次の値にしないでください: :values',
    'doesnt_start_with' => ':attributeの先頭を次の値にしないでください: :values',
    'email' => ':attributeの形式が正しくありません。',
    'encoding' => ':attributeは:encodingでエンコードしてください。',
    'ends_with' => ':attributeの末尾は次のいずれかにしてください: :values',
    'enum' => ':attributeの値が正しくありません。',
    'exists' => ':attributeの値が正しくありません。',
    'extensions' => ':attributeの拡張子は次のいずれかにしてください: :values',
    'file' => ':attributeにはファイルを指定してください。',
    'filled' => ':attributeを入力してください。',
    'gt' => [
        'array' => ':attributeは:value個より多くしてください。',
        'file' => ':attributeは:value KB より大きいファイルにしてください。',
        'numeric' => ':attributeは:valueより大きい値にしてください。',
        'string' => ':attributeは:value文字より多くしてください。',
    ],
    'gte' => [
        'array' => ':attributeは:value個以上にしてください。',
        'file' => ':attributeは:value KB 以上のファイルにしてください。',
        'numeric' => ':attributeは:value以上の値にしてください。',
        'string' => ':attributeは:value文字以上にしてください。',
    ],
    'hex_color' => ':attributeには有効な16進数の色を指定してください。',
    'image' => ':attributeには画像を指定してください。',
    'in' => ':attributeの値が正しくありません。',
    'in_array' => ':attributeは:otherに含まれる値にしてください。',
    'in_array_keys' => ':attributeには次のキーのうち少なくとも1つを含めてください: :values',
    'integer' => ':attributeは整数で指定してください。',
    'ip' => ':attributeには有効な IP アドレスを指定してください。',
    'ipv4' => ':attributeには有効な IPv4 アドレスを指定してください。',
    'ipv6' => ':attributeには有効な IPv6 アドレスを指定してください。',
    'json' => ':attributeには有効な JSON の文字列を指定してください。',
    'list' => ':attributeにはリストを指定してください。',
    'lowercase' => ':attributeは小文字で入力してください。',
    'lt' => [
        'array' => ':attributeは:value個より少なくしてください。',
        'file' => ':attributeは:value KB より小さいファイルにしてください。',
        'numeric' => ':attributeは:valueより小さい値にしてください。',
        'string' => ':attributeは:value文字より少なくしてください。',
    ],
    'lte' => [
        'array' => ':attributeは:value個以下にしてください。',
        'file' => ':attributeは:value KB 以下のファイルにしてください。',
        'numeric' => ':attributeは:value以下の値にしてください。',
        'string' => ':attributeは:value文字以下にしてください。',
    ],
    'mac_address' => ':attributeには有効な MAC アドレスを指定してください。',
    'max' => [
        'array' => ':attributeは:max個以下にしてください。',
        'file' => ':attributeは:max KB 以下のファイルにしてください。',
        'numeric' => ':attributeは:max以下の値にしてください。',
        'string' => ':attributeは:max文字以内で入力してください。',
    ],
    'max_digits' => ':attributeは:max桁以下にしてください。',
    'mimes' => ':attributeには次の形式のファイルを指定してください: :values',
    'mimetypes' => ':attributeには次の形式のファイルを指定してください: :values',
    'min' => [
        'array' => ':attributeは:min個以上にしてください。',
        'file' => ':attributeは:min KB 以上のファイルにしてください。',
        'numeric' => ':attributeは:min以上で指定してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
    ],
    'min_digits' => ':attributeは:min桁以上にしてください。',
    'missing' => ':attributeは指定しないでください。',
    'missing_if' => ':otherが:valueのときは、:attributeを指定しないでください。',
    'missing_unless' => ':otherが:valueでないときは、:attributeを指定しないでください。',
    'missing_with' => ':valuesがあるときは、:attributeを指定しないでください。',
    'missing_with_all' => ':valuesがすべてあるときは、:attributeを指定しないでください。',
    'multiple_of' => ':attributeは:valueの倍数にしてください。',
    'not_in' => ':attributeの値が正しくありません。',
    'not_regex' => ':attributeの形式が正しくありません。',
    'numeric' => ':attributeには数値を指定してください。',
    'password' => [
        'letters' => ':attributeには英字を1文字以上含めてください。',
        'mixed' => ':attributeには大文字と小文字をそれぞれ1文字以上含めてください。',
        'numbers' => ':attributeには数字を1文字以上含めてください。',
        'symbols' => ':attributeには記号を1文字以上含めてください。',
        'uncompromised' => 'この:attributeは過去に漏えいしたことがあります。別の:attributeにしてください。',
    ],
    'present' => ':attributeを指定してください。',
    'present_if' => ':otherが:valueのときは、:attributeを指定してください。',
    'present_unless' => ':otherが:valueでないときは、:attributeを指定してください。',
    'present_with' => ':valuesがあるときは、:attributeを指定してください。',
    'present_with_all' => ':valuesがすべてあるときは、:attributeを指定してください。',
    'prohibited' => ':attributeは指定できません。',
    'prohibited_if' => ':otherが:valueのときは、:attributeを指定できません。',
    'prohibited_if_accepted' => ':otherを承認したときは、:attributeを指定できません。',
    'prohibited_if_declined' => ':otherを拒否したときは、:attributeを指定できません。',
    'prohibited_unless' => ':otherが:valuesのいずれかでないときは、:attributeを指定できません。',
    'prohibits' => ':attributeがあるときは、:otherを指定できません。',
    'regex' => ':attributeの形式が正しくありません。',
    'required' => ':attributeを入力してください。',
    'required_array_keys' => ':attributeには次の項目を含めてください: :values',
    'required_if' => ':otherが:valueのときは、:attributeを入力してください。',
    'required_if_accepted' => ':otherを承認したときは、:attributeを入力してください。',
    'required_if_declined' => ':otherを拒否したときは、:attributeを入力してください。',
    'required_unless' => ':otherが:valuesのいずれかでないときは、:attributeを入力してください。',
    'required_with' => ':valuesがあるときは、:attributeを入力してください。',
    'required_with_all' => ':valuesがすべてあるときは、:attributeを入力してください。',
    'required_without' => ':valuesがないときは、:attributeを入力してください。',
    'required_without_all' => ':valuesがどれもないときは、:attributeを入力してください。',
    'same' => ':attributeと:otherが一致しません。',
    'size' => [
        'array' => ':attributeは:size個にしてください。',
        'file' => ':attributeは:size KB のファイルにしてください。',
        'numeric' => ':attributeは:sizeにしてください。',
        'string' => ':attributeは:size文字にしてください。',
    ],
    'starts_with' => ':attributeの先頭は次のいずれかにしてください: :values',
    'string' => ':attributeは文字で入力してください。',
    'timezone' => ':attributeには有効なタイムゾーンを指定してください。',
    'unique' => 'この:attributeはすでに使われています。',
    'uploaded' => ':attributeをアップロードできませんでした。',
    'uppercase' => ':attributeは大文字で入力してください。',
    'url' => ':attributeには有効な URL を指定してください。',
    'ulid' => ':attributeには有効な ULID を指定してください。',
    'uuid' => ':attributeには有効な UUID を指定してください。',

    /*
    |--------------------------------------------------------------------------
    | 項目ごとのメッセージ
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 項目名
    |--------------------------------------------------------------------------
    |
    | 認証と設定の画面（Fortify・スターターキット）で使う項目名。
    | かんばんの項目名は、それぞれの Request の attributes() に書いている。
    |
    */

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認）',
        'current_password' => '現在のパスワード',
        'code' => '認証コード',
        'recovery_code' => 'リカバリーコード',
    ],

];
