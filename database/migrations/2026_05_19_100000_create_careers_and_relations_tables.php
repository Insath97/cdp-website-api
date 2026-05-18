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
        // 1. Careers Primary Table
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('poster_image')->nullable();
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('job_type')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        // 2. Responsibilities Master Table
        Schema::create('responsibilities', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->timestamps();
        });

        // 3. Requirements Master Table
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->timestamps();
        });

        // 4. Benefits Master Table
        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->timestamps();
        });

        // 5. Career Responsibilities Pivot Table
        Schema::create('career_responsibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_id')->constrained('careers')->cascadeOnDelete();
            $table->foreignId('responsibility_id')->constrained('responsibilities')->cascadeOnDelete();
            $table->timestamps();
        });

        // 6. Career Requirements Pivot Table
        Schema::create('career_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_id')->constrained('careers')->cascadeOnDelete();
            $table->foreignId('requirement_id')->constrained('requirements')->cascadeOnDelete();
            $table->timestamps();
        });

        // 7. Career Benefits Pivot Table
        Schema::create('career_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('career_id')->constrained('careers')->cascadeOnDelete();
            $table->foreignId('benefit_id')->constrained('benefits')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_benefits');
        Schema::dropIfExists('career_requirements');
        Schema::dropIfExists('career_responsibilities');
        Schema::dropIfExists('benefits');
        Schema::dropIfExists('requirements');
        Schema::dropIfExists('responsibilities');
        Schema::dropIfExists('careers');
    }
};
