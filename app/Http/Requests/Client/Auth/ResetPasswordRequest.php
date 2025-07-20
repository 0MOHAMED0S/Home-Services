<?php

namespace App\Http\Requests\Client\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'regex:/^20(10|11|12|15)[0-9]{8}$/',
                'exists:users,phone',
            ],
            'request_id' => 'required|string',
            'code' => 'required|string',

            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone number is required.',
            'phone.string' => 'Phone number must be a string.',
            'phone.regex' => 'Phone number must be a valid Egyptian number starting with 20.',
            'phone.exists' => 'This phone number is not registered.',

            'request_id.required' => 'Request ID is required.',
            'request_id.string' => 'Request ID must be a string.',

            'code.required' => 'Verification code is required.',
            'code.string' => 'Verification code must be a string.',

            'password.required' => 'Password is required.',
            'password.string' => 'Password must be a valid string.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 100 characters.',
            'password.regex' => 'Password must contain uppercase, lowercase, number, and symbol.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
