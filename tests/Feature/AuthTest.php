<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\User;
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

    public function test_user_endpoint_requires_auth(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_logout_endpoint_requires_auth(): void
    {
        $this->postJson('/api/logout')
            ->assertUnauthorized();
    }

    public function test_register_requires_valid_email(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_strong_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_requires_name(): void
    {
        $response = $this->postJson('/api/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::create([
            'name' => 'Existing',
            'email' => 'dup@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'dup@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_rejects_wrong_credentials(): void
    {
        User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
    }

    public function test_login_rejects_unknown_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable();
    }

    public function test_user_attempts_are_isolated_per_user(): void
    {
        $alice = User::create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => bcrypt('password123'),
        ]);

        $bob = User::create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => bcrypt('password123'),
        ]);

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

        $aliceToken = $alice->createToken('api')->plainTextToken;
        $bobToken = $bob->createToken('api')->plainTextToken;

        $this->withToken($aliceToken)->postJson('/api/speaking/submit', [
            'question_id' => $question->id,
            'answer_text' => 'Alice answer with enough characters here.',
        ])->assertCreated();

        $aliceAttempts = $this->withToken($aliceToken)->getJson('/api/attempts');
        $aliceAttempts->assertOk()
            ->assertJsonCount(1, 'data');

        $this->app['auth']->forgetGuards();

        $bobAttempts = $this->withToken($bobToken)->getJson('/api/attempts');
        $bobAttempts->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
