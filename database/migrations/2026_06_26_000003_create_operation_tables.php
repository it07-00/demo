<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_responsibilities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('no')->unique();
            $table->string('phase');
            $table->string('name');
            $table->timestamps();

            $table->index('phase');
        });

        Schema::create('operation_projects', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('customer');
            $table->string('customer_type');
            $table->string('branch');
            $table->string('product');
            $table->string('method');
            $table->string('policy');
            $table->unsignedInteger('unit_price');
            $table->string('recruit_status');
            $table->string('manager_external_id');
            $table->string('manager_name');
            $table->boolean('unassigned')->default(false);
            $table->json('team');
            $table->string('status');
            $table->unsignedInteger('demand');
            $table->unsignedInteger('actual');
            $table->unsignedInteger('shortage');
            $table->unsignedTinyInteger('progress');
            $table->date('contract_start');
            $table->date('contract_end');
            $table->unsignedInteger('paused_days')->default(0);
            $table->boolean('reported_today')->default(false);
            $table->json('docs')->nullable();
            $table->timestamps();

            $table->index(['branch', 'status']);
            $table->index('customer');
            $table->index('manager_external_id');
        });

        Schema::create('operation_recruitment_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operation_project_id')->constrained('operation_projects')->cascadeOnDelete();
            $table->date('report_date');
            $table->string('branch');
            $table->string('customer');
            $table->string('manager');
            $table->unsignedInteger('demand');
            $table->string('method');
            $table->unsignedInteger('registered');
            $table->unsignedInteger('interviewed');
            $table->unsignedInteger('passed');
            $table->unsignedInteger('started');
            $table->unsignedInteger('partner_trial')->default(0);
            $table->string('rank', 1);
            $table->string('reporter');
            $table->time('reported_at')->nullable();
            $table->text('issues')->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamps();

            $table->unique(['operation_project_id', 'report_date'], 'op_reports_project_date_unique');
            $table->index(['report_date', 'branch']);
        });

        Schema::create('operation_receivables', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('customer');
            $table->unsignedInteger('amount');
            $table->date('due_date');
            $table->string('state');
            $table->string('note');
            $table->boolean('paid')->default(false);
            $table->timestamps();

            $table->index(['customer', 'due_date']);
        });

        Schema::create('operation_crm_customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('type');
            $table->string('stage');
            $table->unsignedTinyInteger('stage_idx');
            $table->string('relationship');
            $table->string('contact_name');
            $table->string('contact_role');
            $table->unsignedInteger('revenue_monthly')->default(0);
            $table->date('last_meeting');
            $table->date('next_meeting');
            $table->json('notes');
            $table->timestamps();

            $table->index(['stage_idx', 'relationship']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_crm_customers');
        Schema::dropIfExists('operation_receivables');
        Schema::dropIfExists('operation_recruitment_reports');
        Schema::dropIfExists('operation_projects');
        Schema::dropIfExists('operation_responsibilities');
    }
};
