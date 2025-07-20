<?php

namespace App\Http\Requests\Client\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'freelancer_id'   => 'required|exists:freelancers,id',
            'quoted_price'    => 'required|numeric|min:0',
            'billing_unit'    => 'required|in:per_hour,per_day,per_week,per_month,fixed_price',
            'city'            => 'required|string|max:255',
            'country'         => 'required|string|max:255',
            'payment_method'  => 'required|in:cash,online',
            'start_date'      => 'required|date|after_or_equal:today',
            'description'     => 'required|string|max:1000',
        ];
    }
}
