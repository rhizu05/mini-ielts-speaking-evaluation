<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitSpeakingRequest;
use App\Models\Attempt;
use App\Models\Question;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SpeakingController extends Controller
{
    public function submit(SubmitSpeakingRequest $request, GeminiService $gemini): JsonResponse
    {
        $question = Question::findOrFail($request->input('question_id'));

        $attempt = Attempt::create([
            'user_id' => $request->user('sanctum')?->id,
            'question_id' => $question->id,
            'answer_text' => $request->input('answer_text'),
        ]);

        try {
            $feedback = $gemini->evaluate(
                $question->question_text,
                $attempt->answer_text,
            );
        } catch (\Throwable $e) {
            Log::error('Gemini evaluation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Your answer was saved, but evaluation failed. Please try again later.',
                'data' => $attempt->fresh(),
            ], 201);
        }

        $attempt->update([
            'band_score' => $feedback['band_score'],
            'strengths' => $feedback['strengths'],
            'improvements' => $feedback['improvements'],
            'raw_feedback' => $feedback['raw_feedback'],
        ]);

        return response()->json([
            'data' => $attempt->fresh(),
        ], 201);
    }
}
