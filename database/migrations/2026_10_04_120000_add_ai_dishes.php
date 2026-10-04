<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->string('source', 10)->default('manual')->after('is_published'); // manual|seed|ai
            $table->text('image_prompt')->nullable()->after('image_url');
        });

        Schema::table('diet_plan_requests', function (Blueprint $table) {
            $table->unsignedSmallInteger('dishes_pending')->default(0)->after('status');
        });

        // Which dish a plan meal (by title) resolved to: existing catalogue dish or a newly generated one.
        Schema::create('diet_plan_request_dish', function (Blueprint $table) {
            $table->foreignId('diet_plan_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->string('meal_title');
            $table->boolean('created')->default(false); // true = dish was generated for this plan
            $table->primary(['diet_plan_request_id', 'dish_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_plan_request_dish');
        Schema::table('diet_plan_requests', fn (Blueprint $table) => $table->dropColumn('dishes_pending'));
        Schema::table('dishes', fn (Blueprint $table) => $table->dropColumn(['source', 'image_prompt']));
    }
};
