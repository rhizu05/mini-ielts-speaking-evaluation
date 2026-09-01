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
}
