<?php

namespace App\Http\Requests;

use App\Domain\Task\ValueObjects\TaskPriority;
use App\Domain\Task\ValueObjects\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * TaskController 用。ルートごとに検証する項目を分ける。
 *
 * - projects.tasks.store / tasks.update: タスクの内容
 * - tasks.move: 移動先（status, position）と、画面を表示したときのボードの版
 *
 * 担当者がメンバーかどうかは、業務ルールとして UseCase で確かめる。
 *
 * status / priority は Rule::enum ではなく、enum の値から作った Rule::in で検証する。
 * Rule::enum のエラーメッセージは翻訳ファイルからしか取れず、messages() で日本語にできないため。
 */
class TaskRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->routeIs('tasks.move')) {
            return [
                'status' => ['required', Rule::in(array_column(TaskStatus::cases(), 'value'))],
                'position' => ['required', 'integer', 'min:0'],
                'expected_version' => ['required', 'integer', 'min:0'],
            ];
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_column(TaskStatus::cases(), 'value'))],
            'priority' => ['required', Rule::in(array_column(TaskPriority::cases(), 'value'))],
            'assignee_id' => ['nullable', 'integer'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'description' => '説明',
            'status' => 'ステータス',
            'priority' => '優先度',
            'assignee_id' => '担当者',
            'due_date' => '期限',
            'position' => '位置',
            'expected_version' => 'ボードの版',
        ];
    }

    /**
     * 日付は :format（Y-m-d）より YYYY-MM-DD の方が伝わりやすいので、ここだけ個別に書く
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'due_date.date_format' => ':attributeは YYYY-MM-DD の形式で入力してください。',
        ];
    }
}
