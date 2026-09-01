<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    public function index(): JsonResponse
    {
        $questions = Question::query()
            ->orderBy('part')
            ->orderBy('id')
            ->get(['id', 'part', 'topic', 'question_text']);

        return response()->json([
            'data' => $questions,
        ]);
    }
}
