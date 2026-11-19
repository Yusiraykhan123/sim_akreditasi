<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('standard', 50);
            $table->string('ep', 20);
            $table->text('content');
            $table->string('rdwos', 100)->nullable();
            $table->timestamps();
            $table->index(['category_id', 'standard', 'ep']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elements');
    }
};
