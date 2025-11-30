<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionVersion;
use Illuminate\Http\Request;

class SurveyQuestionController extends Controller
{
    public function index()
    {
        $items = SurveyQuestion::where('active', true)
            ->orderBy('id')
            ->get()
            ->map(function($q){
                $ver = SurveyQuestionVersion::where('question_id', $q->id)->orderByDesc('version')->first();
                return [
                    'id' => $q->id,
                    'title' => $q->title,
                    'subtitle' => $q->subtitle,
                    'version' => $ver ? $ver->version : 1,
                ];
            });
        return response()->json(['questions' => $items]);
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable','integer','exists:survey_questions,id'],
            'title' => ['required','string','max:255'],
            'subtitle' => ['nullable','string','max:255'],
            'active' => ['required','boolean'],
        ]);

        if (!empty($data['id'])) {
            $q = SurveyQuestion::find($data['id']);
            $q->update($data);
        } else {
            $q = SurveyQuestion::create($data);
        }

        // create new version snapshot
        $lastVer = SurveyQuestionVersion::where('question_id', $q->id)->max('version');
        $ver = ($lastVer ?? 0) + 1;
        SurveyQuestionVersion::create([
            'question_id' => $q->id,
            'version' => $ver,
            'title' => $q->title,
            'subtitle' => $q->subtitle,
            'changed_by' => optional($request->user())->id,
        ]);

        return response()->json(['question' => $q, 'version' => $ver]);
    }
}
