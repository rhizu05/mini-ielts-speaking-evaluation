<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpeakingTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_questions(): void
    {
        Question::create([
            'part' => 1,
            'topic' => 'Hometown',
            'question_text' => 'Where is your hometown?',
        ]);

        $response = $this->getJson('/api/questions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'part', 'topic', 'question_text']],
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_submit_speaking_answer(): void
    {
        $question = Question::create([
            'part' => 2,
            'topic' => 'Technology',
            'question_text' => 'Describe a piece of technology you use.',
        ]);

        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('evaluate')
                ->once()
                ->andReturn([
                    'band_score' => 7.5,
                    'strengths' => ['Good fluency'],
                    'improvements' => ['Improve grammar'],
                    'raw_feedback' => 'Nice job.',
                ]);
        });

        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => $question->id,
            'answer_text' => 'I use my phone every day because it helps me work and communicate with others.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.strengths', ['Good fluency'])
            ->assertJsonPath('data.improvements', ['Improve grammar']);

        $this->assertDatabaseHas('attempts', [
            'question_id' => $question->id,
        ]);
    }

    public function test_submit_rejects_invalid_payload(): void
    {
        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => 9999,
            'answer_text' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['question_id', 'answer_text']);
    }

    public function test_submit_requires_question_id(): void
    {
        $response = $this->postJson('/api/speaking/submit', [
            'answer_text' => 'This is a valid answer with enough characters.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['question_id']);
    }

    public function test_submit_requires_answer_text(): void
    {
        $question = Question::create([
            'part' => 1,
            'topic' => 'Hometown',
            'question_text' => 'Where is your hometown?',
        ]);

        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => $question->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['answer_text']);
    }

    public function test_submit_rejects_nonexistent_question(): void
    {
        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => 99999,
            'answer_text' => 'This is a valid answer with enough characters.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['question_id']);
    }

    public function test_submit_rejects_answer_under_minimum_length(): void
    {
        $question = Question::create([
            'part' => 1,
            'topic' => 'Hometown',
            'question_text' => 'Where is your hometown?',
        ]);

        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => $question->id,
            'answer_text' => 'too short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['answer_text']);
    }

    public function test_submit_rejects_answer_over_maximum_length(): void
    {
        $question = Question::create([
            'part' => 1,
            'topic' => 'Hometown',
            'question_text' => 'Where is your hometown?',
        ]);

        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => $question->id,
            'answer_text' => str_repeat('a', 2001),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['answer_text']);
    }

    public function test_submit_rejects_non_integer_question_id(): void
    {
        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => 'abc',
            'answer_text' => 'This is a valid answer with enough characters.',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['question_id']);
    }

    public function test_submit_still_saves_attempt_when_gemini_returns_invalid_data(): void
    {
        $question = Question::create([
            'part' => 1,
            'topic' => 'Hometown',
            'question_text' => 'Where is your hometown?',
        ]);

        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('evaluate')
                ->once()
                ->andReturn([
                    'band_score' => null,
                    'strengths' => [],
                    'improvements' => [],
                    'raw_feedback' => 'garbage output',
                ]);
        });

        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => $question->id,
            'answer_text' => 'This is a valid answer with enough characters.',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('attempts', [
            'question_id' => $question->id,
        ]);
    }

    public function test_submit_returns_201_when_gemini_throws_exception(): void
    {
        $question = Question::create([
            'part' => 1,
            'topic' => 'Hometown',
            'question_text' => 'Where is your hometown?',
        ]);

        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('evaluate')
                ->once()
                ->andThrow(new \RuntimeException('Gemini failed'));
        });

        $response = $this->postJson('/api/speaking/submit', [
            'question_id' => $question->id,
            'answer_text' => 'This is a valid answer with enough characters.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Your answer was saved, but evaluation failed. Please try again later.');

        $this->assertDatabaseHas('attempts', [
            'question_id' => $question->id,
        ]);
    }

    public function test_submit_guest_does_not_link_attempt_to_user(): void
    {
        $question = Question::create([
            'part' => 1,
            'topic' => 'Hometown',
            'question_text' => 'Where is your hometown?',
        ]);

        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('evaluate')->andReturn([
                'band_score' => 6.0,
                'strengths' => ['Clear'],
                'improvements' => ['Detail'],
                'raw_feedback' => 'Good.',
            ]);
        });

        $this->postJson('/api/speaking/submit', [
            'question_id' => $question->id,
            'answer_text' => 'This is a valid answer with enough characters.',
        ])->assertCreated();

        $this->assertDatabaseHas('attempts', [
            'question_id' => $question->id,
            'user_id' => null,
        ]);
    }
}
