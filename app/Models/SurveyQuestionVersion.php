<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestionVersion extends Model
{
    protected $fillable = ['question_id','version','title','subtitle','changed_by'];
}
