<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public_survey_responses', function (Blueprint $table) {
            $table->id();
            $table->string('country')->nullable();
            $table->string('email')->nullable();
            $table->string('service')->nullable();
            $table->timestamp('submitted_at');
            $table->json('ratings');
            $table->text('suggestion')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('public_survey_responses');
    }
};
