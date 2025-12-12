<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('response_ratings', function (Blueprint $table) {
            $table->string('question_title', 200)->nullable()->after('question_id');
            $table->index('question_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('response_ratings', function (Blueprint $table) {
            $table->dropIndex(['question_title']);
            $table->dropColumn('question_title');
        });
    }
};
