<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * メールアドレスのドメインが config('tasuku.allowed_email_domains') に含まれるか。
 *
 * 大文字・小文字は区別しない。許可リストが空なら、どのアドレスも通さない。
 */
class AllowedEmailDomain implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /** @var list<string> $allowed */
        $allowed = config('tasuku.allowed_email_domains', []);

        $email = is_string($value) ? $value : '';
        $at = strrpos($email, '@');
        $domain = $at === false ? '' : strtolower(substr($email, $at + 1));

        if ($domain === '' || ! in_array($domain, $allowed, true)) {
            $fail('このメールアドレスのドメインでは登録できません。社内のメールアドレスを使ってください。');
        }
    }
}
