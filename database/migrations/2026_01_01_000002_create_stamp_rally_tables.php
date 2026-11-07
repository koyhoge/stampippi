<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rally_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('visibility')->default('public'); // draft | public | unlisted
            $table->timestamps();
        });

        Schema::create('stamp_rallies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rally_series_id')->nullable()->constrained('rally_series')->nullOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('visibility')->default('public'); // draft | public | unlisted
            $table->string('completion_condition')->default('all'); // all | count
            $table->unsignedInteger('required_locations_count')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('stamp_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stamp_rally_id')->constrained('stamp_rallies')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(100);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->string('stamp_icon_type')->default('preset'); // preset | custom
            $table->string('stamp_icon_value')->default('star');
            $table->string('stamp_color')->default('#4F46E5');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('rally_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stamp_rally_id')->constrained('stamp_rallies')->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'stamp_rally_id']);
        });

        Schema::create('user_stamps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stamp_rally_id')->constrained('stamp_rallies')->cascadeOnDelete();
            $table->foreignId('stamp_location_id')->constrained('stamp_locations')->cascadeOnDelete();
            $table->decimal('checkin_lat', 10, 7);
            $table->decimal('checkin_lng', 10, 7);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->unsignedInteger('distance_meters');
            $table->timestamp('stamped_at');
            $table->timestamps();

            $table->unique(['user_id', 'stamp_location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_stamps');
        Schema::dropIfExists('rally_participations');
        Schema::dropIfExists('stamp_locations');
        Schema::dropIfExists('stamp_rallies');
        Schema::dropIfExists('rally_series');
    }
};
