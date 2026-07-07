<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatBotController extends Controller
{
    public function __construct(private readonly ChatBotService $chatBotService) {}

    /**
     * Handle the incoming chat message and return a response.
     */
    public function message(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $result = $this->chatBotService->ask($validated['message']);

        return response()->json([
            'answer' => $result['answer'],
            'gemini_connected' => $result['gemini_connected'],
        ]);
    }
}
