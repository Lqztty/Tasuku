<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ProjectMemberController 用（projects.members.store）。
 *
 * ユーザーが登録済みか・すでにメンバーかは、業務ルールとして UseCase で確かめる。
 */
class ProjectMemberRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'メールアドレス',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attributeを入力してください。',
            'string' => ':attributeは文字で入力してください。',
            'email' => ':attributeの形式が正しくありません。',
            'max' => ':attributeは:max文字以内で入力してください。',
        ];
    }
}
