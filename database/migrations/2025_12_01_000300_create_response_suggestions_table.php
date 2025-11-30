<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('response_suggestions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('response_id');
            $table->text('suggestion_text')->nullable();
            $table->timestamps();
            $table->index(['response_id']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('response_suggestions');
    }
};
