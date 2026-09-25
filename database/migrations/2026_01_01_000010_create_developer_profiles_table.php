<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('developer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('github_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('completed_tasks')->default(0);
            $table->unsignedInteger('rejected_tasks')->default(0);
            $table->decimal('approval_rate', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('developer_profiles');
    }
};
