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
        Schema::create('career_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_code')->unique();
            $table->foreignId('career_id')->constrained('careers')->cascadeOnDelete();
            $table->string('fullname');
            $table->string('email');
            $table->string('phone_number');
            $table->string('resume_path');
            $table->text('cover_letter')->nullable();
            $table->string('status')->default('applied');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_applications');
    }
};
