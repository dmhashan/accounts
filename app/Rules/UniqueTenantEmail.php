<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueTenantEmail implements ValidationRule
{
    protected $tenantId;
    protected $ignoreUserId;

    public function __construct($tenantId, $ignoreUserId = null)
    {
        $this->tenantId = $tenantId;
        $this->ignoreUserId = $ignoreUserId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = User::where('tenant_id', $this->tenantId)
            ->where('email', $value);
        
        if ($this->ignoreUserId) {
            $query->where('id', '!=', $this->ignoreUserId);
        }
        
        if ($query->exists()) {
            $fail('The :attribute has already been taken for this tenant.');
        }
    }
}
