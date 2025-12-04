<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('public_survey_responses', function (Blueprint $table) {
            $table->string('name')->nullable()->after('email');
            $table->json('services')->nullable()->after('service');
        });
    }

    public function down(): void
    {
        Schema::table('public_survey_responses', function (Blueprint $table) {
            $table->dropColumn(['name', 'services']);
        });
    }
};
