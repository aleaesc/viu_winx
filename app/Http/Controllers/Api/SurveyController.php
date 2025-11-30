<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Survey\CreateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;

class SurveyController extends Controller
{
    public function store(CreateSurveyRequest $request)
    {
        $survey = Survey::create($request->validated());
        return new SurveyResource($survey);
    }

    public function show(Survey $survey)
    {
        return new SurveyResource($survey);
    }

    public function index()
    {
        return SurveyResource::collection(Survey::latest()->get());
    }
}
