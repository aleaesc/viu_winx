<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PublicSurveyResponse;
use App\Models\SurveyQuestionVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class PublicSurveyResponseController extends Controller
{
    public function index(Request $request)
    {
        // Return all responses with ratings for admin listing
        $responses = PublicSurveyResponse::orderBy('submitted_at', 'desc')->get();
        $result = $responses->map(function($r) {
            // Get ratings with question titles, using left join to handle missing versions
            $ratings = DB::table('response_ratings')
                ->leftJoin('survey_question_versions', function($join) {
                    $join->on('response_ratings.question_id', '=', 'survey_question_versions.question_id')
                         ->on('response_ratings.question_version', '=', 'survey_question_versions.version');
                })
                ->where('response_ratings.response_id', $r->id)
                ->select(
                    DB::raw('COALESCE(survey_question_versions.title, "Question") as title'),
                    'response_ratings.rating'
                )
                ->get()
                ->map(function($rating) {
                    return [
                        'title' => $rating->title,
                        'rating' => $rating->rating
                    ];
                })
                ->toArray();
                
            $suggestion = DB::table('response_suggestions')
                ->where('response_id', $r->id)
                ->value('suggestion_text');
            return [
                'id' => $r->id,
                'country' => $r->country,
                'name' => $r->name,
                'service' => $r->service,
                'services' => $r->services,
                'email' => $r->email,
                'submitted_at' => $r->submitted_at,
                'ratings' => $ratings,
                'suggestion' => $suggestion,
            ];
        });
        return response()->json($result);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'country' => ['nullable','string','max:100'],
            'email' => ['nullable','email','max:150'],
            'name' => ['nullable','string','max:150'],
            'service' => ['nullable','string','max:100'],
            'services' => ['nullable','array'],
            'services.*' => ['string','max:100'],
            'ratings' => ['required','array'],
            'ratings.*.title' => ['required','string','max:200'],
            'ratings.*.question_id' => ['nullable','integer'],
            'ratings.*.rating' => ['required','integer','min:0','max:5'],
            'suggestion' => ['nullable','string','max:2000'],
            'submitted_at' => ['nullable','date'],
        ]);
        $data['submitted_at'] = $data['submitted_at'] ?? now();

        // Idempotency guard: prevent rapid duplicate submissions (e.g., multiple clicks)
        try {
            $ratingsForHash = array_map(function($r){
                return [
                    'id' => $r['question_id'] ?? null,
                    'title' => $r['title'] ?? null,
                    'rating' => $r['rating'] ?? null,
                ];
            }, $data['ratings'] ?? []);
            // Stable hash based on email/ip + ratings + day
            $idKeyBase = strtolower(trim($data['email'] ?? '')) ?: ('ip:'.$request->ip());
            $idKey = hash('sha256', json_encode([
                'k' => $idKeyBase,
                'r' => $ratingsForHash,
                'd' => date('Y-m-d', strtotime((string)$data['submitted_at']))
            ]));
            $cacheKey = 'idem:survey:'.$idKey;
            // Only allow first create within 60 seconds; subsequent duplicates return 202
            if (!Cache::add($cacheKey, '1', 60)) {
                return response()->json(['message' => 'Duplicate submission ignored'], 202);
            }
        } catch (\Throwable $e) {
            // Do not block if hashing fails; proceed normally
        }

        return DB::transaction(function() use ($data) {
            $resp = PublicSurveyResponse::create($data);
            foreach ($data['ratings'] as $r) {
                $qid = $r['question_id'] ?? null;
                $ver = null;
                if ($qid) {
                    $ver = SurveyQuestionVersion::where('question_id', $qid)->max('version');
                    if (!$ver) { $ver = 1; }
                }
                DB::table('response_ratings')->insert([
                    'response_id' => $resp->id,
                    'question_id' => $qid,
                    'question_version' => $ver,
                    'rating' => $r['rating'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            if (!empty($data['suggestion'])) {
                DB::table('response_suggestions')->insert([
                    'response_id' => $resp->id,
                    'suggestion_text' => $data['suggestion'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return response()->json(['id' => $resp->id], 201);
        });
    }

    public function destroy($id)
    {
        $response = PublicSurveyResponse::find($id);
        if (!$response) {
            return response()->json(['message' => 'Response not found'], 404);
        }
        
        return DB::transaction(function() use ($response) {
            // Delete associated ratings and suggestions
            DB::table('response_ratings')->where('response_id', $response->id)->delete();
            DB::table('response_suggestions')->where('response_id', $response->id)->delete();
            $response->delete();
            return response()->json(['message' => 'Deleted successfully'], 200);
        });
    }
}
