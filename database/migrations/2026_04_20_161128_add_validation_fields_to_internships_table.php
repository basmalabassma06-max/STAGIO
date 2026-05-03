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
        Schema::table('internships', function (Blueprint $table) {
    $table->unsignedBigInteger('validated_by')->nullable();
    $table->timestamp('validated_at')->nullable();
    $table->unsignedBigInteger('rejected_by')->nullable();
    $table->timestamp('rejected_at')->nullable();

    $table->foreign('validated_by')->references('id')->on('users')->nullOnDelete();
    $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            //
        });
    }
};
