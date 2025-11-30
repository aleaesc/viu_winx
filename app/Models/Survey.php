<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    protected $fillable = ['title','description','questions'];
    protected $casts = ['questions' => 'array'];

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }
}
