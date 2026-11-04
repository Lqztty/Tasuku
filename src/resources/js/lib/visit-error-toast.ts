import type { VisitOptions } from '@inertiajs/core';
import { toast } from 'sonner';

/**
 * router.delete / router.patch などで、失敗したときにトーストで知らせるためのコールバック。
 *
 * <Form> や useForm と違い、router の呼び出しはエラーを表示する場所を持たないため、
 * これを渡さないと失敗しても画面に何も出ない。
 *
 * - 422（入力・業務ルールのエラー）: サーバーが返した最初のメッセージ
 * - 403 / 404 / 500 など: 「○○できませんでした」。Inertia の既定のエラー表示は出さない
 * - 通信エラー: 接続の確認を促す
 *
 * @param action - 「削除」「移動」など、何に失敗したか
 */
export function visitErrorToast(
    action: string,
): Pick<VisitOptions, 'onError' | 'onHttpException' | 'onNetworkError'> {
    return {
        onError: (errors) => {
            const first = Object.values(errors)[0];
            toast.error(
                typeof first === 'string' && first !== ''
                    ? first
                    : `${action}できませんでした。`,
            );
        },
        onHttpException: () => {
            toast.error(
                `${action}できませんでした。画面を再読み込みしてください。`,
            );

            return false;
        },
        onNetworkError: () => {
            toast.error('通信できませんでした。接続を確認してください。');

            return false;
        },
    };
}
