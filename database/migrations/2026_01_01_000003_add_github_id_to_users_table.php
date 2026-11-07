<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'github_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('github_id')->nullable()->unique()->after('google_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'github_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['github_id']);
                $table->dropColumn('github_id');
            });
        }
    }
};

