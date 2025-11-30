<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('survey_question_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->unsignedInteger('version');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();
            $table->unique(['question_id','version']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('survey_question_versions');
    }
};
