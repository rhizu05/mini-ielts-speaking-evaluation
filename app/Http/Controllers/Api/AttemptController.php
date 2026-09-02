<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttemptController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $attempts = $request->user('sanctum')
            ->attempts()
            ->with('question:id,part,topic,question_text')
            ->latest()
            ->get();

        return response()->json(['data' => $attempts]);
    }
}
