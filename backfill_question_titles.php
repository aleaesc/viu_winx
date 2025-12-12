<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Backfilling question titles...\n";

// Set variables for row numbering
DB::statement('SET @row_num = 0, @response = 0');

// Update ratings with question titles based on order
$sql = "UPDATE response_ratings rr 
JOIN (
    SELECT id, response_id, 
    @row_num := IF(@response = response_id, @row_num + 1, 1) as row_num,
    @response := response_id 
    FROM response_ratings 
    ORDER BY response_id, id
) ranked ON rr.id = ranked.id 
SET rr.question_title = CASE ranked.row_num 
    WHEN 1 THEN 'Content Variety'
    WHEN 2 THEN 'Streaming Quality'
    WHEN 3 THEN 'Discovery & Search'
    WHEN 4 THEN 'Subtitles & Dubbing'
    WHEN 5 THEN 'App Performance'
    WHEN 6 THEN 'Value for Money'
    WHEN 7 THEN 'Download Feature'
    WHEN 8 THEN 'Ad Experience (If applicable)'
    WHEN 9 THEN 'Account Management'
    WHEN 10 THEN 'Personalized Recommendations'
    ELSE 'Other'
END
WHERE rr.question_title IS NULL";

$affected = DB::update($sql);

echo "Backfill completed! Updated {$affected} ratings.\n";
