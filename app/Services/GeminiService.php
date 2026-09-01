<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta',
        private readonly string $model = 'gemini-flash-lite-latest',
    ) {}

    public function evaluate(string $question, string $answer): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }

        $response = Http::withHeaders(['x-goog-api-key' => $this->apiKey])
            ->acceptJson()
            ->post("{$this->baseUrl}/models/{$this->model}:generateContent", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $this->buildPrompt($question, $answer)],
                        ],
                    ],
                ],
            ])
            ->throw();

        $raw = $response->json('candidates.0.content.parts.0.text');

        return $this->parse($raw);
    }

    private function buildPrompt(string $question, string $answer): string
    {
        return <<<PROMPT
You are an IELTS Speaking examiner. Evaluate the candidate's answer below.

Question: {$question}

Candidate answer: {$answer}

Return ONLY a valid JSON object with exactly these keys:
{
  "band_score": <number with one decimal, e.g. 6.5>,
  "strengths": ["<short strength>", ...],
  "improvements": ["<short area to improve>", ...],
  "feedback": "<one short paragraph of overall feedback>"
}
Do not wrap the JSON in markdown. Do not add extra text.
PROMPT;
    }

    private function parse(string $raw): array
    {
        $cleaned = trim($raw);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);

        $data = json_decode($cleaned, true);

        if (! is_array($data)) {
            return [
                'band_score' => null,
                'strengths' => [],
                'improvements' => [],
                'raw_feedback' => $raw,
            ];
        }

        return [
            'band_score' => (float) ($data['band_score'] ?? 0),
            'strengths' => array_values(array_filter((array) ($data['strengths'] ?? []))),
            'improvements' => array_values(array_filter((array) ($data['improvements'] ?? []))),
            'raw_feedback' => $data['feedback'] ?? $raw,
        ];
    }
}
