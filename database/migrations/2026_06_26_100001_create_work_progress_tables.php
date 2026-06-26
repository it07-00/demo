<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operation_project_id')->constrained('operation_projects')->cascadeOnDelete();
            $table->smallInteger('year');
            $table->unsignedTinyInteger('week_number');
            $table->date('week_start');
            $table->date('week_end');
            $table->unsignedInteger('customer_demand')->comment('Nhu cầu tuần từ KH');
            $table->unsignedInteger('manager_accepted')->comment('Số QLVH nhận');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['operation_project_id', 'year', 'week_number'], 'wt_project_year_week_unique');
        });

        Schema::create('weekly_target_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('weekly_target_id')->constrained('weekly_targets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->comment('Chuyên viên vận hành');
            $table->unsignedInteger('assigned_quantity')->comment('Số lượng được giao');
            $table->foreignId('assigned_by')->constrained('users')->comment('QLVH phân công');
            $table->timestamps();

            $table->unique(['weekly_target_id', 'user_id'], 'wta_target_user_unique');
        });

        Schema::create('daily_progress_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('weekly_target_assignment_id')->constrained('weekly_target_assignments')->cascadeOnDelete();
            $table->date('entry_date');
            $table->unsignedInteger('achieved')->default(0)->comment('Số đạt được trong ngày');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['weekly_target_assignment_id', 'entry_date'], 'dpe_assignment_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_progress_entries');
        Schema::dropIfExists('weekly_target_assignments');
        Schema::dropIfExists('weekly_targets');
    }
};
