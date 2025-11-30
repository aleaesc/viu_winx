<?php

namespace App\Http\Requests\Survey;

use Illuminate\Foundation\Http\FormRequest;

class CreateSurveyRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'questions' => ['required','array','min:1'],
            'questions.*.id' => ['required','string'],
            'questions.*.type' => ['required','in:text,select,radio,checkbox'],
            'questions.*.label' => ['required','string'],
            'questions.*.options' => ['nullable','array'],
        ];
    }
}
