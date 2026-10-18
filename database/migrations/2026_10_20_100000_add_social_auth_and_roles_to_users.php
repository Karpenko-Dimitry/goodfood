<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar', 500)->nullable()->after('google_id');
            $table->boolean('is_admin')->default(false)->after('avatar');
            $table->string('password')->nullable()->change();   // Google-only accounts have no password
        });

        // Until now only administrators had accounts (no public registration): keep their access.
        DB::table('users')->update(['is_admin' => true]);

        Schema::table('diet_plan_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('diet_plan_requests', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'avatar', 'is_admin']);
        });
    }
};
