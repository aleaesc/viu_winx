<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SurveyAnswerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'survey' => new SurveyResource($this->whenLoaded('survey', $this->survey)),
            'user' => new UserResource($this->whenLoaded('user', $this->user)),
            'answers' => $this->answers,
            'submitted_at' => $this->created_at,
        ];
    }
}
