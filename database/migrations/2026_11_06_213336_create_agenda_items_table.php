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
        Schema::create('agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('language', 2)->default('en');
            $table->string('slides')->nullable();
            $table->timestamps();

            $table->index('starts_at');
        });

        Schema::create('agenda_item_speaker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('speaker_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_moderator')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['agenda_item_id', 'speaker_id']);
        });

        Schema::create('agenda_item_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

            $table->unique(['agenda_item_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_item_tag');
        Schema::dropIfExists('agenda_item_speaker');
        Schema::dropIfExists('agenda_items');
    }
};
