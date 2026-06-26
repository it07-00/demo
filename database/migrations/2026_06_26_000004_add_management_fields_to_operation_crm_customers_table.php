<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operation_crm_customers', function (Blueprint $table): void {
            $table->string('contact_phone')->nullable()->after('contact_role');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->string('source')->nullable()->after('contact_email');
            $table->string('priority')->default('Bình thường')->after('source');
            $table->string('owner_name')->nullable()->after('priority');
            $table->string('next_action')->nullable()->after('next_meeting');
            $table->boolean('active')->default(true)->after('next_action');

            $table->index(['priority', 'active']);
            $table->index('owner_name');
        });
    }

    public function down(): void
    {
        Schema::table('operation_crm_customers', function (Blueprint $table): void {
            $table->dropIndex(['priority', 'active']);
            $table->dropIndex(['owner_name']);
            $table->dropColumn([
                'contact_phone',
                'contact_email',
                'source',
                'priority',
                'owner_name',
                'next_action',
                'active',
            ]);
        });
    }
};
