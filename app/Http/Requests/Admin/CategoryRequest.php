<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
    $categoryId = $this->route('category')?->id ?? null; // handle both model & ID route

    $rules = [];

    if ($this->isMethod('post')) {
        // For store
        $rules['name'] = 'required|string|unique:categories,name';
        $rules['path'] = 'required|image|mimes:jpg,jpeg,png,webp|max:2048';
    } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
        // For update
        $rules['name'] = 'nullable|string|unique:categories,name,' . $categoryId;
        $rules['path'] = 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048';
    }

    return $rules;
}


    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.unique' => 'This name already exists.',
            'path.required' => 'An image file is required.',
            'path.image' => 'The path must be an image file.',
            'path.mimes' => 'Only jpg, jpeg, png, and webp are allowed.',
            'path.max' => 'Image size must be less than 2MB.',
        ];
    }
}
