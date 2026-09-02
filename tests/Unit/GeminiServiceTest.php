<?php

namespace Tests\Unit;

use App\Services\GeminiService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    private function service(): GeminiService
    {
        return new GeminiService(apiKey: 'test-key');
    }

    public function test_evaluate_parses_valid_json_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"band_score": 6.5, "strengths": ["Fluency"], "improvements": ["Grammar"], "feedback": "Good."}']]]],
                ],
            ]),
        ]);

        $result = $this->service()->evaluate('Question', 'Answer');

        $this->assertSame(6.5, $result['band_score']);
        $this->assertSame(['Fluency'], $result['strengths']);
        $this->assertSame(['Grammar'], $result['improvements']);
        $this->assertSame('Good.', $result['raw_feedback']);
    }

    public function test_evaluate_parses_markdown_wrapped_json(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => "```json\n{\"band_score\": 7.0, \"strengths\": [\"Clear\"], \"improvements\": [\"Detail\"], \"feedback\": \"Ok.\"}\n```"]]]],
                ],
            ]),
        ]);

        $result = $this->service()->evaluate('Question', 'Answer');

        $this->assertSame(7.0, $result['band_score']);
        $this->assertSame(['Clear'], $result['strengths']);
    }

    public function test_evaluate_handles_plain_text_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'I cannot evaluate this answer.']]]],
                ],
            ]),
        ]);

        $result = $this->service()->evaluate('Question', 'Answer');

        $this->assertNull($result['band_score']);
        $this->assertSame([], $result['strengths']);
        $this->assertSame([], $result['improvements']);
        $this->assertSame('I cannot evaluate this answer.', $result['raw_feedback']);
    }

    public function test_evaluate_handles_json_with_missing_keys(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"band_score": 5.0}']]]],
                ],
            ]),
        ]);

        $result = $this->service()->evaluate('Question', 'Answer');

        $this->assertSame(5.0, $result['band_score']);
        $this->assertSame([], $result['strengths']);
        $this->assertSame([], $result['improvements']);
    }

    public function test_evaluate_handles_empty_text_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '']]]],
                ],
            ]),
        ]);

        $result = $this->service()->evaluate('Question', 'Answer');

        $this->assertNull($result['band_score']);
        $this->assertSame([], $result['strengths']);
        $this->assertSame([], $result['improvements']);
        $this->assertSame('', $result['raw_feedback']);
    }

    public function test_evaluate_handles_string_values_in_arrays(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"band_score": "6.0", "strengths": "fluent", "improvements": null, "feedback": "ok"}']]]],
                ],
            ]),
        ]);

        $result = $this->service()->evaluate('Question', 'Answer');

        $this->assertSame(6.0, $result['band_score']);
        $this->assertSame([], $result['improvements']);
    }

    public function test_evaluate_throws_on_http_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'something went wrong'], 500),
        ]);

        $this->expectException(RequestException::class);

        $this->service()->evaluate('Question', 'Answer');
    }

    public function test_evaluate_throws_when_api_key_is_missing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GEMINI_API_KEY is not configured.');

        (new GeminiService(apiKey: ''))->evaluate('Question', 'Answer');
    }
}
