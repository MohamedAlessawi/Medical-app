<?php

// database/migrations/2025_08_14_000001_update_ratings_to_polymorphic.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->unsignedBigInteger('rateable_id')->nullable()->after('user_id');
            $table->string('rateable_type')->nullable()->after('rateable_id');
            $table->index(['rateable_id', 'rateable_type']);
            $table->unique(
                ['appointment_id','user_id','rateable_id','rateable_type'],
                'ratings_unique_per_target'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropUnique('ratings_unique_per_target');
            $table->dropIndex(['rateable_id', 'rateable_type']);
            $table->dropColumn(['rateable_id','rateable_type']);
        });
    }
};
