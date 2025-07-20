<?php

namespace App\Rules;

use App\Models\Admin;
use App\Models\Freelancer;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniquePhoneAcrossGuards implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $phoneExists = User::where('phone', $value)->exists()
        || Admin::where('phone', $value)->exists()
        ||Freelancer::where('phone', $value)->exists();

        if ($phoneExists) {
            $fail('The phone number is already used by another account .');
        }
    }
}
