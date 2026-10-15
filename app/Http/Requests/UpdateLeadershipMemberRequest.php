<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLeadershipMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'section' => 'sometimes|required|string|in:chairman,executive,specialised',
            'name' => 'sometimes|required|string|max:255',
            'designation' => 'sometimes|required|string|max:255',
            'group_label' => 'nullable|string|max:255',
            'summary' => 'nullable|string',
            'executive_statement' => 'nullable|string',
            'message' => 'nullable|string',
            'focus_area' => 'nullable|string|max:255',
            'linkedin' => 'nullable|url|max:500',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'qualifications' => 'nullable|array',
            'qualifications.*' => 'string|max:300',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errorMessages = $validator->errors();

        $fieldErrors = collect($errorMessages->getMessages())->map(function ($messages, $field) {
            return [
                'field' => $field,
                'messages' => $messages,
            ];
        })->values();

        $message = $fieldErrors->count() > 1
            ? 'There are multiple validation errors. Please review the form and correct the issues.'
            : 'There is an issue with the input for ' . $fieldErrors->first()['field'] . '.';

        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $fieldErrors,
        ], 422));
    }
}
