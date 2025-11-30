<?php

namespace App\Http\Requests\Survey;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'survey_id' => ['required','exists:surveys,id'],
            'answers' => ['required','array'],
        ];
    }
}
