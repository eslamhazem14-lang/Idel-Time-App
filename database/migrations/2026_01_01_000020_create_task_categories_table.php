<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('icon', 40)->default('square');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('task_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('type', 40);
            $table->string('title');
            $table->text('description');
            $table->text('instructions');
            $table->unsignedSmallInteger('estimated_minutes');
            $table->decimal('suggested_reward', 10, 2);
            $table->string('difficulty', 10)->default('easy');
            $table->json('required_skills')->nullable();
            $table->text('answer_format')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_templates');
        Schema::dropIfExists('task_categories');
    }
};
