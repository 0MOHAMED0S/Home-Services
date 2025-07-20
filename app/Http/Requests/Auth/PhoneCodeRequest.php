<?php

namespace App\Http\Requests\Auth;

use App\Rules\UniquePhoneAcrossGuards;
use Illuminate\Foundation\Http\FormRequest;

class PhoneCodeRequest extends FormRequest
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
            'phone' => ['required', 'string', 'regex:/^20(10|11|12|15)[0-9]{8}$/',new UniquePhoneAcrossGuards()],

            'request_id' => 'required|string',
            'code' => 'required|digits:4,6',

            'name' => [
                'required',
                'string',
                'min:3',
                'max:100',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                'confirmed'
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must be a valid Egyptian mobile number.',

            'request_id.required' => 'Request ID is required.',
            'code.required' => 'Verification code is required.',
            'code.digits' => 'Verification code must be 4–6 digits.',

            'name.required' => 'Name is required.',
            'name.min' => 'Name must be at least 3 characters.',
            'name.max' => 'Name must not exceed 100 characters.',

            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must contain uppercase, lowercase, number, and symbol.',
            'password_confirmation.required' => 'Password confirmation is required.',
            'password_confirmation.same' => 'Password confirmation does not match.',
            'password.confirmed' => 'Password confirmation does not match.',

        ];
    }
}
