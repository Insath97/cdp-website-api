<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leadership_members', function (Blueprint $table) {
            $table->id();
            $table->enum('section', ['chairman', 'executive', 'specialised'])->default('executive');
            $table->string('name');
            $table->string('designation');
            $table->string('group_label')->nullable();
            $table->text('summary')->nullable();
            $table->text('executive_statement')->nullable();
            $table->text('message')->nullable();
            $table->string('focus_area')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('photo')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leadership_members');
    }
};
