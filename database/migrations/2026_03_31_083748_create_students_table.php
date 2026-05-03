<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('university');
            $table->string('wilaya');
            $table->string('github_link')->nullable();
             $table->text('education')->nullable();
        $table->text('experience')->nullable();
          $table->text('projects')->nullable();
        $table->string('linkedin')->nullable();
        $table->string('portfolio')->nullable();
        
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};