<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueTenantEmail implements ValidationRule
{
    protected $tenantId;

    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = User::where('tenant_id', $this->tenantId)
            ->where('email', $value)
            ->exists();
            
        if ($exists) {
            $fail('The :attribute has already been taken for this tenant.');
        }
    }
}
