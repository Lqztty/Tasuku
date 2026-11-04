/**
 * パスキーの操作で起きたエラーを、画面に出す日本語の文にする
 *
 * @laravel/passkeys はブラウザ側のエラー（キャンセル、登録済みなど）を英語の固定文で返す。
 * サーバーから返る文は lang/ja.json で日本語になっているので、そのまま使う。
 * どちらでもない英語の文（ブラウザ自身のエラーなど）は、共通の文に置き換える。
 * @param error - 失敗したときの Error
 */
export function passkeyErrorMessage(error: Error): string {
    switch (error.name) {
        case 'UserCancelledError':
            return 'パスキーの操作をキャンセルしました。';
        case 'PasskeyExistsError':
            return 'この端末はすでにパスキーとして登録されています。';
        case 'NotSupportedError':
            return 'このブラウザはパスキーに対応していません。';
        case 'InvalidDomainError':
            return 'このドメインではパスキーを使えません。';
    }

    if (JAPANESE.test(error.message)) {
        return error.message;
    }

    return 'パスキーの操作に失敗しました。もう一度お試しください。';
}

// ひらがな・カタカナ・漢字のどれかを含めば、サーバーで日本語にした文とみなす
const JAPANESE = /[぀-ヿ一-鿿]/;
