<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index()
    {
        $range = request()->query('range');
        $fromDate = null;
        if ($range === '7d') {
            $fromDate = now()->subDays(7);
        } elseif ($range === '30d') {
            $fromDate = now()->subDays(30);
        }

        // Total submissions (with optional date filter)
        $totalQuery = DB::table('public_survey_responses');
        if ($fromDate) {
            $totalQuery->where('submitted_at', '>=', $fromDate);
        }
        $total = $totalQuery->count();

        // Overall average from normalized ratings (date filter requires join)
        $overallAvgQuery = DB::table('response_ratings as rr')
            ->join('public_survey_responses as r', 'rr.response_id', '=', 'r.id');
        if ($fromDate) {
            $overallAvgQuery->where('r.submitted_at', '>=', $fromDate);
        }
        $overallAvg = (float) $overallAvgQuery->avg('rr.rating');

        // Per-question averages and counts (using question_title from response_ratings)
        $questionQuery = DB::table('response_ratings as rr')
            ->join('public_survey_responses as r', 'rr.response_id', '=', 'r.id')
            ->selectRaw('COALESCE(rr.question_title, "Untitled") as title, AVG(rr.rating) as avg_rating, COUNT(*) as ratings_count');
        if ($fromDate) {
            $questionQuery->where('r.submitted_at', '>=', $fromDate);
        }
        $rows = $questionQuery
            ->groupBy('rr.question_title')
            ->orderBy('title')
            ->get();

        // Country breakdown
        $countryQuery = DB::table('public_survey_responses')
            ->selectRaw('COALESCE(country, "Unknown") as country, COUNT(*) as submissions');
        if ($fromDate) {
            $countryQuery->where('submitted_at', '>=', $fromDate);
        }
        $countries = $countryQuery->groupBy('country')->orderByDesc('submissions')->get();

        // Service breakdown
        $serviceQuery = DB::table('public_survey_responses')
            ->selectRaw('COALESCE(service, "Unknown") as service, COUNT(*) as submissions');
        if ($fromDate) {
            $serviceQuery->where('submitted_at', '>=', $fromDate);
        }
        $services = $serviceQuery->groupBy('service')->orderByDesc('submissions')->get();

        // Time trend (daily)
        $trendQuery = DB::table('response_ratings as rr')
            ->join('public_survey_responses as r', 'rr.response_id', '=', 'r.id')
            ->selectRaw('DATE(r.submitted_at) as date, COUNT(DISTINCT r.id) as submissions, AVG(rr.rating) as avg_rating');
        if ($fromDate) {
            $trendQuery->where('r.submitted_at', '>=', $fromDate);
        }
        $trends = $trendQuery->groupBy(DB::raw('DATE(r.submitted_at)'))
            ->orderBy('date', 'asc')
            ->get();

        // ================= Additional Metrics =================
        // Collect all ratings (optionally filtered) for distribution stats
        $ratingsBase = DB::table('response_ratings as rr')
            ->join('public_survey_responses as r', 'rr.response_id', '=', 'r.id');
        if ($fromDate) {
            $ratingsBase->where('r.submitted_at', '>=', $fromDate);
        }
        $allRatings = $ratingsBase->pluck('rr.rating');
        $overallMedian = $this->computeMedian($allRatings);
        $overallStdDev = $this->computeStdDev($allRatings);
        $overallPercentiles = $this->computePercentiles($allRatings, [0.25, 0.50, 0.75]);

        // Rating distribution counts overall
        $overallDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $overallDistribution[$i] = $allRatings->where(fn($v) => (int)$v === $i)->count();
        }
        $distTotal = max(1, array_sum($overallDistribution));
        $overallDistributionPct = [];
        foreach ($overallDistribution as $score => $cnt) {
            $overallDistributionPct[$score] = round(($cnt / $distTotal) * 100, 2);
        }

        // Per-question distributions and stats
        $questionDistributions = [];
        $questionRatingsMap = [];
        foreach ($rows as $qRow) {
            $qtitle = $qRow->title;
            $qrBase = DB::table('response_ratings as rr')
                ->join('public_survey_responses as r', 'rr.response_id', '=', 'r.id')
                ->where('rr.question_title', $qtitle);
            if ($fromDate) { $qrBase->where('r.submitted_at', '>=', $fromDate); }
            $ratings = $qrBase->pluck('rr.rating');
            $questionRatingsMap[$qtitle] = $ratings;
            $dist = [];
            for ($i = 1; $i <= 5; $i++) { $dist[$i] = $ratings->where(fn($v) => (int)$v === $i)->count(); }
            $qTotal = max(1, array_sum($dist));
            $distPct = [];
            foreach ($dist as $score => $cnt) { $distPct[$score] = round(($cnt / $qTotal) * 100, 2); }
            $questionDistributions[$qtitle] = [
                'counts' => $dist,
                'percents' => $distPct,
                'median' => $this->computeMedian($ratings),
                'std_dev' => $this->computeStdDev($ratings),
                'p25' => $this->computePercentiles($ratings, [0.25])[0] ?? null,
                'p50' => $this->computePercentiles($ratings, [0.50])[0] ?? null,
                'p75' => $this->computePercentiles($ratings, [0.75])[0] ?? null,
            ];
        }

        // Country-Service pivot
        $pivotQuery = DB::table('public_survey_responses');
        if ($fromDate) { $pivotQuery->where('submitted_at', '>=', $fromDate); }
        $pivotRaw = $pivotQuery->selectRaw('COALESCE(country, "Unknown") as country, COALESCE(service, "Unknown") as service, COUNT(*) as submissions')
            ->groupBy('country', 'service')->get();
        $countryServicePivot = [];
        foreach ($pivotRaw as $row) {
            $country = $row->country; $service = $row->service;
            if (!isset($countryServicePivot[$country])) $countryServicePivot[$country] = [];
            $countryServicePivot[$country][$service] = $row->submissions;
        }

        // Service affinity per question (avg rating per question per service)
        $affinityQuery = DB::table('response_ratings as rr')
            ->join('public_survey_responses as r', 'rr.response_id', '=', 'r.id')
            ->selectRaw('COALESCE(rr.question_title, "Untitled") as title, COALESCE(r.service, "Unknown") as service, AVG(rr.rating) as avg_rating');
        if ($fromDate) { $affinityQuery->where('r.submitted_at', '>=', $fromDate); }
        $affinityRows = $affinityQuery->groupBy('rr.question_title', 'r.service')->get();
        $serviceAffinity = [];
        foreach ($affinityRows as $ar) {
            $qtitle = $ar->title;
            if (!isset($serviceAffinity[$qtitle])) $serviceAffinity[$qtitle] = ['title' => $ar->title, 'services' => []];
            $serviceAffinity[$qtitle]['services'][$ar->service] = round($ar->avg_rating, 2);
        }

        // Email domain breakdown
        $emailQuery = DB::table('public_survey_responses')->select('email');
        if ($fromDate) { $emailQuery->where('submitted_at', '>=', $fromDate); }
        $emails = $emailQuery->get()->pluck('email')->filter();
        $domainCounts = [];
        foreach ($emails as $em) {
            if (strpos($em, '@') !== false) {
                $domain = strtolower(substr(strrchr($em, '@'), 1));
                $domainCounts[$domain] = ($domainCounts[$domain] ?? 0) + 1;
            }
        }
        arsort($domainCounts);
        $emailDomains = [];
        foreach ($domainCounts as $d => $c) { $emailDomains[] = ['domain' => $d, 'count' => $c]; }

        // Hourly heatmap (submissions & avg rating by hour UTC)
        $hourQuery = DB::table('response_ratings as rr')
            ->join('public_survey_responses as r', 'rr.response_id', '=', 'r.id')
            ->selectRaw('HOUR(r.submitted_at) as hour, COUNT(DISTINCT r.id) as submissions, AVG(rr.rating) as avg_rating');
        if ($fromDate) { $hourQuery->where('r.submitted_at', '>=', $fromDate); }
        $hourRows = $hourQuery->groupBy(DB::raw('HOUR(r.submitted_at)'))->orderBy('hour')->get();
        $hourlyHeatmap = [];
        foreach ($hourRows as $hr) { $hourlyHeatmap[] = ['hour' => (int)$hr->hour, 'submissions' => (int)$hr->submissions, 'avg_rating' => round((float)$hr->avg_rating, 2)]; }

        // Previous period comparisons for deltas & movement
        $prevFrom = null; $prevTo = null;
        if ($fromDate) { // previous window of same length ending at fromDate
            $prevTo = clone $fromDate; // end is fromDate
            $lengthDays = $range === '7d' ? 7 : ($range === '30d' ? 30 : null);
            if ($lengthDays) { $prevFrom = (clone $fromDate)->subDays($lengthDays); }
        }
        $prevTotal = null; $prevAvg = null; $questionPrevAvgs = [];
        if ($prevFrom && $prevTo) {
            $prevTotal = DB::table('public_survey_responses')->whereBetween('submitted_at', [$prevFrom, $prevTo])->count();
            $prevAvg = (float) DB::table('response_ratings as rr')
                ->join('public_survey_responses as r', 'rr.response_id', '=', 'r.id')
                ->whereBetween('r.submitted_at', [$prevFrom, $prevTo])
                ->avg('rr.rating');
            // per-question previous averages
            $prevQRows = DB::table('response_ratings as rr')
                ->join('public_survey_responses as r', 'rr.response_id', '=', 'r.id')
                ->selectRaw('rr.question_title, AVG(rr.rating) as avg_rating')
                ->whereBetween('r.submitted_at', [$prevFrom, $prevTo])
                ->groupBy('rr.question_title')->get();
            foreach ($prevQRows as $pqr) { $questionPrevAvgs[$pqr->question_title] = (float)$pqr->avg_rating; }
        }
        // Movement (improvers / decliners)
        $movement = ['improved' => [], 'declined' => []];
        if ($prevAvg !== null && $prevTotal !== null) {
            foreach ($rows as $qRow) {
                $qtitle = $qRow->title; $curAvg = (float)$qRow->avg_rating; $oldAvg = $questionPrevAvgs[$qtitle] ?? null;
                if ($oldAvg !== null) {
                    $diff = $curAvg - $oldAvg; // positive = improvement
                    $movement['improved'][] = ['title' => $qtitle, 'diff' => round($diff, 2)];
                    $movement['declined'][] = ['title' => $qtitle, 'diff' => round($diff, 2)];
                }
            }
            // Filter improved (positive diffs) & declined (negative diffs)
            $movement['improved'] = array_values(array_filter($movement['improved'], fn($m) => $m['diff'] > 0));
            usort($movement['improved'], fn($a,$b)=> $b['diff'] <=> $a['diff']);
            $movement['improved'] = array_slice($movement['improved'], 0, 5);
            $movement['declined'] = array_values(array_filter($movement['declined'], fn($m) => $m['diff'] < 0));
            usort($movement['declined'], fn($a,$b)=> $a['diff'] <=> $b['diff']);
            $movement['declined'] = array_slice($movement['declined'], 0, 5);
        }

        // Engagement metrics
        $possibleRatings = $total * max(1, $rows->count());
        $answeredRatings = DB::table('response_ratings as rr')
            ->join('public_survey_responses as r', 'rr.response_id', '=', 'r.id');
        if ($fromDate) { $answeredRatings->where('r.submitted_at', '>=', $fromDate); }
        $answeredCount = $answeredRatings->count();
        $completionRate = $possibleRatings > 0 ? round(($answeredCount / $possibleRatings) * 100, 2) : 0;
        $suggestionQuery = DB::table('response_suggestions as rs')
            ->join('public_survey_responses as r', 'rs.response_id', '=', 'r.id');
        if ($fromDate) { $suggestionQuery->where('r.submitted_at', '>=', $fromDate); }
        $suggestionCount = $suggestionQuery->count();
        $suggestionRate = $total > 0 ? round(($suggestionCount / $total) * 100, 2) : 0;
        $positiveRatings = $allRatings->where(fn($v) => (int)$v >= 4)->count();
        $negativeRatings = $allRatings->where(fn($v) => (int)$v <= 2)->count();
        $satisfactionIndex = $distTotal > 0 ? round((($positiveRatings / $distTotal) * 100) - (($negativeRatings / $distTotal) * 100), 2) : 0;
        $dataQuality = [
            'missing_country' => DB::table('public_survey_responses')->when($fromDate, fn($q) => $q->where('submitted_at', '>=', $fromDate))->whereNull('country')->count(),
            'missing_service' => DB::table('public_survey_responses')->when($fromDate, fn($q) => $q->where('submitted_at', '>=', $fromDate))->whereNull('service')->count(),
            'blank_email' => DB::table('public_survey_responses')->when($fromDate, fn($q) => $q->where('submitted_at', '>=', $fromDate))->whereNull('email')->count(),
        ];

        // Week-over-week / month-over-month deltas (if range specified)
        $delta = null;
        if ($prevAvg !== null && $prevTotal !== null && $fromDate) {
            $delta = [
                'avg_rating_change_pct' => ($prevAvg == 0 ? null : round((($overallAvg - $prevAvg) / $prevAvg) * 100, 2)),
                'submission_change_pct' => ($prevTotal == 0 ? null : round((($total - $prevTotal) / $prevTotal) * 100, 2)),
            ];
        }

        // Anomaly days (avg deviates >2 std dev from mean across days in range)
        $dayAvgs = [];
        foreach ($trends as $t) { $dayAvgs[] = (float)$t->avg_rating; }
        $dayStd = $this->computeStdDev(collect($dayAvgs)); $dayMean = empty($dayAvgs) ? 0 : array_sum($dayAvgs)/max(1,count($dayAvgs));
        $anomalies = [];
        if ($dayStd > 0) {
            foreach ($trends as $t) {
                $val = (float)$t->avg_rating;
                if (abs($val - $dayMean) > (2 * $dayStd)) {
                    $anomalies[] = ['date' => $t->date, 'avg_rating' => round($val,2), 'deviation' => round($val - $dayMean,2)];
                }
            }
        }

        return response()->json([
            'range' => $range ?? 'all',
            'total_submissions' => $total,
            'overall_average' => round($overallAvg, 2),
            'questions' => $rows,
            'countries' => $countries,
            'services' => $services,
            'trends' => $trends,
            'overall_distribution' => ['counts' => $overallDistribution, 'percents' => $overallDistributionPct],
            'overall_median' => $overallMedian,
            'overall_std_dev' => $overallStdDev,
            'overall_percentiles' => [
                'p25' => $overallPercentiles[0] ?? null,
                'p50' => $overallPercentiles[1] ?? null,
                'p75' => $overallPercentiles[2] ?? null,
            ],
            'question_stats' => $questionDistributions,
            'country_service_pivot' => $countryServicePivot,
            'service_affinity' => $serviceAffinity,
            'email_domains' => $emailDomains,
            'hourly_heatmap' => $hourlyHeatmap,
            'delta' => $delta,
            'movement' => $movement,
            'engagement' => [
                'completion_rate_pct' => $completionRate,
                'suggestion_rate_pct' => $suggestionRate,
                'satisfaction_index' => $satisfactionIndex,
                'data_quality' => $dataQuality,
            ],
            'anomalies' => $anomalies,
        ]);
    }

    private function computeMedian($collection)
    {
        $arr = $collection instanceof \Illuminate\Support\Collection ? $collection->values()->all() : (array)$collection;
        $count = count($arr);
        if ($count === 0) return null;
        sort($arr);
        $mid = (int) floor($count / 2);
        if ($count % 2) return (float) $arr[$mid];
        return ((float)$arr[$mid - 1] + (float)$arr[$mid]) / 2.0;
    }

    private function computeStdDev($collection)
    {
        $arr = $collection instanceof \Illuminate\Support\Collection ? $collection->values()->all() : (array)$collection;
        $n = count($arr);
        if ($n <= 1) return 0.0;
        $mean = array_sum($arr) / $n;
        $variance = 0.0;
        foreach ($arr as $v) { $variance += pow(($v - $mean), 2); }
        return sqrt($variance / $n);
    }

    private function computePercentiles($collection, $percentiles = [])
    {
        $arr = $collection instanceof \Illuminate\Support\Collection ? $collection->values()->all() : (array)$collection;
        $count = count($arr);
        if ($count === 0) return array_fill(0, count($percentiles), null);
        sort($arr);
        $results = [];
        foreach ($percentiles as $p) {
            if ($p < 0 || $p > 1) { $results[] = null; continue; }
            $rank = $p * ($count - 1);
            $low = (int) floor($rank); $high = (int) ceil($rank);
            if ($low === $high) { $results[] = (float) $arr[$low]; }
            else {
                $weight = $rank - $low;
                $results[] = (float) ($arr[$low] * (1 - $weight) + $arr[$high] * $weight);
            }
        }
        return $results;
    }
}
