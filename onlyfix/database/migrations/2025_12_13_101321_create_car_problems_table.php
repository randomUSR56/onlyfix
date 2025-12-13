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
        Schema::create('car_problems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('problem_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->enum('severity', ['minor', 'moderate', 'severe'])->default('moderate');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('car_id');
            $table->index('problem_id');
            $table->index('ticket_id');
            $table->index('detected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_problems');
    }
};
