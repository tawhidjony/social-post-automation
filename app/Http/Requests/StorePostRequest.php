<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspaceId = $this->user()->ensureCurrentWorkspace()->id;

        return [
            'social_account_ids' => ['required', 'array', 'min:1'],
            'social_account_ids.*' => [
                'integer',
                Rule::exists('social_accounts', 'id')->where('workspace_id', $workspaceId),
            ],
            'content' => ['required_without:media', 'nullable', 'string'],
            'media' => ['nullable', 'array'],
            'media.*' => ['image', 'max:10240'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }
}
