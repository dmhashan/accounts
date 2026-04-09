<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MemberActivityLogController extends Controller
{
    /**
     * List activity logs for the admin SPA (auth + permission required).
     */
    public function index(Request $request)
    {
        $tenant = app('tenant');

        $query = MemberActivityLog::with('member:id,first_name,last_name,name,member_id')
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at');

        // Filters
        if ($request->filled('member_search')) {
            $search = '%'.trim($request->member_search).'%';
            $query->whereHas('member', fn ($q) => $q
                ->where('first_name', 'like', $search)
                ->orWhere('last_name', 'like', $search)
                ->orWhere('name', 'like', $search)
                ->orWhere('member_id', 'like', $search)
            );
        }

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('device_type')) {
            $query->where('device_type', $request->device_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }

        $logs = $query->paginate(25)->through(fn ($log) => $this->formatLog($log));

        return response()->json($logs);
    }

    /**
     * Export activity logs as CSV.
     */
    public function export(Request $request)
    {
        $tenant = app('tenant');

        $query = MemberActivityLog::with('member:id,first_name,last_name,name,member_id')
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at');

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('device_type')) {
            $query->where('device_type', $request->device_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $filename = 'activity-logs-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Date/Time', 'Member ID', 'Member Name', 'Session ID',
                'Event Type', 'Device Type', 'Browser', 'OS',
                'Screen Resolution', 'IP Address', 'User Agent',
            ]);

            $query->chunk(200, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    $memberName = $log->member
                        ? trim(($log->member->first_name ?? '') . ' ' . ($log->member->last_name ?? '')) ?: ($log->member->name ?? '')
                        : '';
                    $resolution = ($log->screen_width && $log->screen_height)
                        ? "{$log->screen_width}x{$log->screen_height}"
                        : '';

                    fputcsv($handle, [
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->member?->member_id ?? '',
                        $memberName,
                        $log->session_id,
                        $log->event_type,
                        $log->device_type ?? '',
                        $log->browser ?? '',
                        $log->os ?? '',
                        $resolution,
                        $log->ip_address ?? '',
                        $log->user_agent ?? '',
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function formatLog(MemberActivityLog $log): array
    {
        $member = $log->member;
        $memberName = $member
            ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: ($member->name ?? '')
            : null;

        return [
            'id'              => $log->id,
            'session_id'      => $log->session_id,
            'event_type'      => $log->event_type,
            'device_type'     => $log->device_type,
            'browser'         => $log->browser,
            'os'              => $log->os,
            'ip_address'      => $log->ip_address,
            'screen_width'    => $log->screen_width,
            'screen_height'   => $log->screen_height,
            'metadata'        => $log->metadata,
            'created_at'      => $log->created_at?->toISOString(),
            'member_id'       => $log->member_id,
            'member_name'     => $memberName,
            'member_ref_id'   => $member?->member_id,
        ];
    }
}
