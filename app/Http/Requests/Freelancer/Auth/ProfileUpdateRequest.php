<?php

namespace App\Http\Requests\Freelancer\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'min:3', 'max:100'],
            'city' => ['sometimes', 'string', 'max:100'],
            'country' => ['sometimes', 'string', 'max:100'],
            'freelancer_type' => ['sometimes', 'in:contract,hourly,set_price'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'description' => ['sometimes', 'string', 'max:1000'],
            'average_price' => ['sometimes', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'The name must be a string.',
            'name.min' => 'The name must be at least 3 characters.',
            'name.max' => 'The name must not exceed 100 characters.',

            'city.string' => 'The city must be a string.',
            'city.max' => 'The city must not exceed 100 characters.',

            'country.string' => 'The country must be a string.',
            'country.max' => 'The country must not exceed 100 characters.',

            'freelancer_type.in' => 'The freelancer type must be one of: contract, hourly, or set_price.',

            'category_id.exists' => 'The selected category does not exist.',

            'path.image' => 'The profile image must be a valid image file.',
            'path.mimes' => 'Only jpg, jpeg, png, and webp images are allowed.',
            'path.max' => 'The profile image size must not exceed 2MB.',

            'description.string' => 'The description must be a string.',
            'description.max' => 'The description must not exceed 1000 characters.',

            'average_price.integer' => 'The average price must be a number.',
            'average_price.min' => 'The average price must be at least 0.',
            'average_price.max' => 'The average price must not exceed 100,000.',
        ];
    }
}
