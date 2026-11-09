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
        Schema::table('stamp_rallies', function (Blueprint $table) {
            $table->string('participant_display_mode', 20)->default('icons')->after('visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stamp_rallies', function (Blueprint $table) {
            $table->dropColumn('participant_display_mode');
        });
    }
};
