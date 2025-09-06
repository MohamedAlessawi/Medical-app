<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // center_id
            if (!Schema::hasColumn('notifications', 'center_id')) {
                $table->foreignId('center_id')
                    ->nullable()
                    ->constrained('centers')
                    ->nullOnDelete(); // أو ->cascadeOnDelete() حسب سياستك
            }

            if (Schema::hasColumn('notifications', 'type') && !Schema::hasColumn('notifications', 'title')) {
                $table->renameColumn('type', 'title');
            }

        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'center_id')) {
                $table->dropConstrainedForeignId('center_id');
            }
            if (Schema::hasColumn('notifications', 'title') && !Schema::hasColumn('notifications', 'type')) {
                $table->renameColumn('title', 'type');
            }

        });
    }
};
