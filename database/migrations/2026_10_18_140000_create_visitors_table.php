<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per browser (cookie). user_id is set once the visitor is signed in.
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();          // anonymized (last IPv4 octet / IPv6 bytes zeroed)
            $table->string('user_agent')->nullable();
            $table->string('device', 10)->nullable();      // desktop | mobile | tablet
            $table->string('locale', 5)->nullable();
            $table->string('referrer')->nullable();        // host the first visit came from
            $table->string('landing_path')->nullable();
            $table->string('last_path')->nullable();
            $table->unsignedInteger('page_views')->default(0);
            $table->timestamp('first_seen_at')->index();
            $table->timestamp('last_seen_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
