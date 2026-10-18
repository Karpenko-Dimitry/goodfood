<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('file_object_id')->constrained('file_objects')->cascadeOnDelete();
            $table->string('key', 20);
            $table->string('path', 1024)->nullable();
            $table->string('ext', 20)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedMediumInteger('width')->nullable();
            $table->unsignedMediumInteger('height')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
