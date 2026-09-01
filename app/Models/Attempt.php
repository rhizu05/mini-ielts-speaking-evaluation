<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'question_id',
    'answer_text',
    'band_score',
    'strengths',
    'improvements',
    'raw_feedback',
])]

class Attempt extends Model
{
    protected function casts(): array
    {
        return [
            'band_score' => 'decimal:1',
            'strengths' => 'array',
            'improvements' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
