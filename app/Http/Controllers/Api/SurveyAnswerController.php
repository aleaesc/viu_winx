<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Survey\SubmitAnswerRequest;
use App\Http\Resources\SurveyAnswerResource;
use App\Models\SurveyAnswer;
use Illuminate\Http\Request;

class SurveyAnswerController extends Controller
{
    public function store(SubmitAnswerRequest $request)
    {
        $user = $request->user();
        $surveyId = $request->validated()['survey_id'];
        $answers = $request->validated()['answers'];
        $existing = SurveyAnswer::where('survey_id', $surveyId)->where('user_id', $user->id)->first();
        if ($existing) {
            return response()->json(['message' => 'Already submitted'], 409);
        }
        $answer = SurveyAnswer::create([
            'survey_id' => $surveyId,
            'user_id' => $user->id,
            'answers' => $answers,
        ]);
        return new SurveyAnswerResource($answer->load(['survey','user']));
    }

    public function mine(Request $request)
    {
        return SurveyAnswerResource::collection(
            SurveyAnswer::with(['survey'])
                ->where('user_id', $request->user()->id)
                ->latest()->get()
        );
    }
}
