<?php

use App\Services\FileService\Models\FileObject;
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
        Schema::create('file_objects', function (Blueprint $table) {
            $table->id();
            $table->uuid()->nullable();
            $table->nullableMorphs('fileable');
            $table->string('original_name', 200)->nullable();
            $table->string('type');
            $table->string('ext', 10);
            $table->string('mime_type')->nullable();
            $table->integer('size')->nullable();
            $table->integer('order')->nullable()->unsigned();
            $table->string('dominant_color', 200)->default('');
            $table->unsignedBigInteger('duration')->nullable();
            $table->nullableMorphs('ownerable');
            $table->string('state')->default(FileObject::STATE_UPLOADED);
            $table->timestamps();

            $table->index(['fileable_id', 'fileable_type', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('file_objects');
    }
};
