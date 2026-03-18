<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ReportApiController extends Controller
{
    public function overview(): JsonResponse
    {
        return response()->json([
            'status' => 'coming-soon',
            'features' => [
                ['title' => 'User Activity', 'description' => 'Track user engagement and activity patterns'],
                ['title' => 'Permission Reports', 'description' => 'Analyze role and permission usage'],
                ['title' => 'Performance Metrics', 'description' => 'Monitor system performance and trends'],
                ['title' => 'Audit Logs', 'description' => 'Review system activity and changes'],
            ],
        ]);
    }
}
