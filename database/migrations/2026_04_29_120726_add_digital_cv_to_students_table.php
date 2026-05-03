<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->text('cv_summary')->nullable()->after('wilaya');
            $table->string('cv_languages')->nullable()->after('cv_summary');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'cv_summary',
                'cv_languages'
            ]);
        });
    }
};