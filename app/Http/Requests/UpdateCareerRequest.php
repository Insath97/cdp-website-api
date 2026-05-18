<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCareerRequest extends FormRequest
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
        $careerId = $this->route('career');

        return [
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:careers,slug,' . $careerId,
            'description' => 'nullable|string',
            'poster_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
            'department' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'job_type' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
            'is_active' => 'nullable|boolean',
            'key_responsibilities' => 'nullable|array',
            'key_responsibilities.*' => 'string|max:1000',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string|max:1000',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string|max:1000',
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
