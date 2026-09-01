<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_see_attempts(): void
    {
        $register = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $register->assertCreated();
        $token = $register->json('data.token');

        $question = Question::create([
            'part' => 1,
            'topic' => 'Hometown',
            'question_text' => 'Where is your hometown?',
        ]);

        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('evaluate')
                ->andReturn([
                    'band_score' => 6.0,
                    'strengths' => ['Clear answer'],
                    'improvements' => ['Add detail'],
                    'raw_feedback' => 'Good.',
                ]);
        });

        $this->withToken($token)->postJson('/api/speaking/submit', [
            'question_id' => $question->id,
            'answer_text' => 'My hometown is a small and peaceful city near the mountains.',
        ])->assertCreated();

        $this->withToken($token)
            ->getJson('/api/attempts')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_attempts_endpoint_requires_auth(): void
    {
        $this->getJson('/api/attempts')
            ->assertUnauthorized();
    }
}
