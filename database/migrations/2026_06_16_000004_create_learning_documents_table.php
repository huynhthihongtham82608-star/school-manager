<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable()->index();
            $table->string('file_url')->nullable();
            $table->string('subject_id', 50)->nullable()->index();
            $table->string('class_id', 50)->nullable()->index();
            $table->string('uploaded_by', 50)->nullable()->index();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_documents');
    }
};
