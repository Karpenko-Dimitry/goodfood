<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diets', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('excerpt')->nullable();
            $table->json('description')->nullable();
            $table->json('pros')->nullable();
            $table->json('cons')->nullable();
            $table->json('allowed_foods')->nullable();
            $table->json('forbidden_foods')->nullable();
            $table->string('image')->nullable();      // uploaded file (public disk)
            $table->string('image_url')->nullable();  // or external URL
            $table->string('icon')->nullable();
            $table->unsignedTinyInteger('difficulty')->default(2); // 1..3
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('calories_min')->nullable();
            $table->unsignedSmallInteger('calories_max')->nullable();
            $table->unsignedTinyInteger('protein_pct')->nullable();
            $table->unsignedTinyInteger('fat_pct')->nullable();
            $table->unsignedTinyInteger('carbs_pct')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('dish_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('excerpt')->nullable();
            $table->json('ingredients')->nullable();
            $table->json('instructions')->nullable();
            $table->string('image')->nullable();      // uploaded file (public disk)
            $table->string('image_url')->nullable();  // or external URL
            $table->unsignedSmallInteger('calories')->default(0);   // kcal per serving
            $table->decimal('protein', 6, 1)->default(0);           // g per serving
            $table->decimal('fat', 6, 1)->default(0);
            $table->decimal('carbs', 6, 1)->default(0);
            $table->unsignedSmallInteger('prep_minutes')->nullable();
            $table->unsignedSmallInteger('cook_minutes')->nullable();
            $table->unsignedTinyInteger('servings')->default(1);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('diet_dish', function (Blueprint $table) {
            $table->foreignId('diet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->primary(['diet_id', 'dish_id']);
        });

        Schema::create('diet_plan_requests', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('locale', 5);
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('gender', 10);
            $table->unsignedTinyInteger('age');
            $table->unsignedSmallInteger('height_cm');
            $table->decimal('weight_kg', 5, 1);
            $table->decimal('target_weight_kg', 5, 1)->nullable();
            $table->string('activity', 20);
            $table->string('goal', 20);
            $table->foreignId('preferred_diet_id')->nullable()->constrained('diets')->nullOnDelete();
            $table->text('preferences')->nullable();
            $table->text('allergies')->nullable();
            $table->unsignedTinyInteger('meals_per_day')->default(4);
            $table->string('status', 20)->default('pending'); // pending|completed|failed
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_plan_requests');
        Schema::dropIfExists('diet_dish');
        Schema::dropIfExists('dishes');
        Schema::dropIfExists('dish_categories');
        Schema::dropIfExists('diets');
    }
};
