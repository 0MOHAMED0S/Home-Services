<?php

namespace App\Http\Requests\Client\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'freelancer_id'   => 'sometimes|exists:freelancers,id',
            'quoted_price'    => 'sometimes|numeric|min:0',
            'billing_unit'    => 'sometimes|in:per_hour,per_day,per_week,per_month,fixed_price',
            'city'            => 'sometimes|string|max:255',
            'country'         => 'sometimes|string|max:255',
            'payment_method'  => 'sometimes|in:cash,online',
            'start_date'      => 'sometimes|date|after_or_equal:today',
            'description'     => 'sometimes|string|max:1000',
        ];
    }
}
