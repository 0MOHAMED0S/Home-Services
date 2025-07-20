<?php

namespace App\Http\Requests\Freelancer\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ProfileStoreRequest extends FormRequest
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
        'name' => ['required', 'string', 'min:3', 'max:100'],
        'city' => ['required', 'string', 'max:100'],
        'country' => ['required', 'string', 'max:100'],
        'freelancer_type' => ['required', 'in:contract,hourly,set_price'],
        'category_id' => ['required', 'exists:categories,id'],
        'path' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        'description' => ['required', 'string', 'max:1000'],
        'average_price' => ['required', 'integer', 'min:0', 'max:100000'],
    ];
}


public function messages(): array
{
    return [
        'name.required' => 'Name is required.',
        'name.string' => 'Name must be a valid string.',
        'name.min' => 'Name must be at least 3 characters.',
        'name.max' => 'Name must not exceed 100 characters.',

        'city.required' => 'City is required.',
        'city.string' => 'City must be a valid string.',
        'city.max' => 'City must not exceed 100 characters.',

        'country.required' => 'Country is required.',
        'country.string' => 'Country must be a valid string.',
        'country.max' => 'Country must not exceed 100 characters.',

        'freelancer_type.required' => 'Freelancer type is required.',
        'freelancer_type.in' => 'Freelancer type must be one of: contract, hourly, or set_price.',

        'category_id.required' => 'Category is required.',
        'category_id.exists' => 'Selected category does not exist.',

        'path.required' => 'Profile image is required.',
        'path.image' => 'The uploaded file must be an image.',
        'path.mimes' => 'Allowed image types are: jpg, jpeg, png, and webp.',
        'path.max' => 'Image size must not exceed 2MB.',

        'description.required' => 'Description is required.',
        'description.string' => 'Description must be a valid string.',
        'description.max' => 'Description must not exceed 1000 characters.',

        'average_price.required' => 'Average price is required.',
        'average_price.integer' => 'Average price must be a number.',
        'average_price.min' => 'Average price must be at least 0.',
        'average_price.max' => 'Average price must not exceed 100,000.',
    ];
}

}
