<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePostRequest extends FormRequest
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
            'content' => ['nullable', 'string'],
            'media' => ['nullable', 'array'],
            'media.*' => ['image', 'max:10240'],
            'existing_media' => ['nullable', 'array'],
            'existing_media.*' => ['string'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $content = $this->input('content');
            $hasContent = is_string($content) && trim($content) !== '';
            $hasNewMedia = $this->hasFile('media');
            $existingMedia = $this->input('existing_media', []);
            $hasExistingMedia = is_array($existingMedia) && count($existingMedia) > 0;

            if (! $hasContent && ! $hasNewMedia && ! $hasExistingMedia) {
                $validator->errors()->add('content', 'A post requires content or media.');
            }
        });
    }
}
