<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ResolvePpToken
{
    public function handle(Request $request, Closure $next)
    {
        $token  = $request->header('X-PP-Token');
        $tenant = app('tenant');

        $data = $token ? Cache::get("pp_token:{$token}") : null;

        if (! $data || $data['tenant_id'] !== $tenant->id) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $request->merge(['_pp_member_id' => $data['member_id']]);

        return $next($request);
    }
}
