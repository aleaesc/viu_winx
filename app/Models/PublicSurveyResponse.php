<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicSurveyResponse extends Model
{
    protected $fillable = ['country','email','service','submitted_at','ratings','suggestion'];
    protected $casts = [ 'ratings' => 'array', 'submitted_at' => 'datetime' ];
}
