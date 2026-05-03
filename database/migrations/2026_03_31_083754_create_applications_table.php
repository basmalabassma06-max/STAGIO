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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            // 👤 student من جدول students
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->onDelete('cascade');

            // 📄 offer
            $table->foreignId('offer_id')
                  ->constrained('offers')
                  ->onDelete('cascade');

            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};