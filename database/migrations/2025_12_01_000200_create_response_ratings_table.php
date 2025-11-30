<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('response_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('response_id');
            $table->unsignedBigInteger('question_id')->nullable();
            $table->unsignedInteger('question_version')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();
            $table->index(['response_id']);
            $table->index(['question_id']);
            $table->index(['question_id','question_version']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('response_ratings');
    }
};
