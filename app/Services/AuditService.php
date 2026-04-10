<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function log(
        int $tenantId,
        string $action,
        Model $subject,
        ?array $before = null,
        ?array $after = null,
    ): AuditLog {
        return AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => get_class($subject),
            'auditable_id' => $subject->getKey(),
            'before_data' => $before,
            'after_data' => $after,
            'created_at' => now(),
        ]);
    }

    public function forModel(int $tenantId, string $type, int $id, int $perPage = 20): array
    {
        $logs = AuditLog::query()
            ->where('tenant_id', $tenantId)
            ->where('auditable_type', $type)
            ->where('auditable_id', $id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            'data' => collect($logs->items())->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->user?->name ?? 'System',
                'before_data' => $log->before_data,
                'after_data' => $log->after_data,
                'created_at' => $log->created_at?->format('d M Y, H:i'),
            ]),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ];
    }

    public function recent(int $tenantId, string $type, int $limit = 50): array
    {
        $logs = AuditLog::query()
            ->where('tenant_id', $tenantId)
            ->where('auditable_type', $type)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $logs->map(fn (AuditLog $log) => [
            'id' => $log->id,
            'action' => $log->action,
            'auditable_id' => $log->auditable_id,
            'user' => $log->user?->name ?? 'System',
            'before_data' => $log->before_data,
            'after_data' => $log->after_data,
            'created_at' => $log->created_at?->format('d M Y, H:i'),
        ])->values()->all();
    }
}
