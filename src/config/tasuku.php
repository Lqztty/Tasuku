<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 登録できるメールアドレスのドメイン
    |--------------------------------------------------------------------------
    |
    | 社内の人だけが登録できるよう、メールアドレスのドメインを制限する。
    | .env の ALLOWED_EMAIL_DOMAINS にカンマ区切りで書く（例: example.co.jp,example.com）。
    | 空のときは誰も登録できない（設定し忘れても外部の人が登録できないよう、安全側に倒す）。
    | サブドメインは自動では含めない。必要なら個別に書く。
    |
    */

    'allowed_email_domains' => array_values(array_filter(array_map(
        fn (string $domain): string => strtolower(trim($domain)),
        explode(',', (string) env('ALLOWED_EMAIL_DOMAINS', '')),
    ))),

];
