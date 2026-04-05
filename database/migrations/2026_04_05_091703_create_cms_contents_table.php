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
        Schema::create('cms_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page')->index();
            $table->string('section')->index();
            $table->string('key')->index();
            $table->text('value')->nullable();
            $table->enum('type', ['text', 'textarea', 'image', 'video', 'pdf', 'svg', 'file', 'link', 'icon'])->default('text');
            $table->string('label')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['page', 'section', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_contents');
    }
};
