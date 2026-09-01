<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['part', 'topic', 'question_text'])]
class Question extends Model
{
    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }
}
