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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('access_token', 64)->unique()->index();
            $table->unsignedInteger('default_sessions_per_week')->default(3);
            $table->unsignedInteger('default_competitions_planned')->default(6);
            $table->string('arbitration_mode')->default('quota');
            $table->unsignedInteger('quota_places')->nullable()->default(12);
            $table->decimal('min_score', 4, 2)->nullable()->default(6.50);
            $table->unsignedInteger('max_volunteering_age')->default(14);
            $table->unsignedInteger('required_volunteering_count')->default(2);
            $table->string('tiiva_id')->nullable();
            $table->timestamps();
        });

        Schema::create('athletes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->unsignedInteger('birth_year');
            $table->string('license_number')->nullable();
            $table->string('tiiva_id')->nullable();
            $table->string('nds_number')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['last_name', 'first_name', 'birth_year']);
        });

        Schema::create('evaluation_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('weeks_count')->default(5);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });

        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('athlete_id')->constrained('athletes')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('evaluation_session_id')->nullable()->constrained('evaluation_sessions')->nullOnDelete();
            $table->foreignId('parent_evaluation_id')->nullable()->constrained('evaluations')->nullOnDelete();
            $table->string('context')->default('collective');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('weeks_count')->default(5);

            // Tech lead / shared values
            $table->unsignedInteger('sessions_per_week')->default(3);
            $table->unsignedInteger('competitions_planned')->default(6);
            $table->unsignedInteger('competitions_done')->default(0);
            $table->unsignedInteger('real_attendances')->nullable();
            $table->unsignedInteger('parent_volunteering_count')->default(0);
            $table->boolean('has_club_engagement')->default(false);

            // Coach editable fields
            $table->boolean('is_injured')->default(false);
            $table->unsignedInteger('lateness_count')->default(0);
            $table->decimal('c4_commitment', 3, 1)->nullable();
            $table->decimal('c5_behavior', 3, 1)->nullable();
            $table->string('c6_level')->nullable();
            $table->decimal('c7_progress', 3, 1)->nullable();
            $table->decimal('c8_environment', 3, 1)->nullable();
            $table->text('coach_notes')->nullable();
            $table->string('status')->default('draft');

            // Calculated engine scores
            $table->decimal('c1_score', 4, 2)->nullable();
            $table->decimal('c2_score', 4, 2)->nullable();
            $table->decimal('c3_score', 4, 2)->nullable();
            $table->decimal('c6_score', 4, 2)->nullable();
            $table->decimal('c9_score', 4, 2)->nullable();
            $table->decimal('base_average', 4, 2)->nullable();
            $table->decimal('bonus_points', 4, 2)->nullable();
            $table->decimal('final_score', 4, 2)->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->string('decision')->default('pending');

            $table->timestamps();

            $table->index(['group_id', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('evaluation_sessions');
        Schema::dropIfExists('athletes');
        Schema::dropIfExists('groups');
    }
};
