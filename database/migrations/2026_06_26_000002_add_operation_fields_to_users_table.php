<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('operation_branch')->nullable()->after('address');
            $table->string('operation_role')->nullable()->after('operation_branch');
            $table->string('employment_status')->nullable()->after('operation_role');

            $table->index('operation_role');
            $table->index('operation_branch');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['operation_role']);
            $table->dropIndex(['operation_branch']);
            $table->dropColumn(['operation_branch', 'operation_role', 'employment_status']);
        });
    }
};
