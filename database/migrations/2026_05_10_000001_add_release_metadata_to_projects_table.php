<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('projects', 'release_branch_patterns')) {
                $table->json('release_branch_patterns')->nullable()->after('active');
            }

            if (! Schema::hasColumn('projects', 'environments')) {
                $table->json('environments')->nullable()->after('release_branch_patterns');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            if (Schema::hasColumn('projects', 'environments')) {
                $table->dropColumn('environments');
            }

            if (Schema::hasColumn('projects', 'release_branch_patterns')) {
                $table->dropColumn('release_branch_patterns');
            }
        });
    }
};
