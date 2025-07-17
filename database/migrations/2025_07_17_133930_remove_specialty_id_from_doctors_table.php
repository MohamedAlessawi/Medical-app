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
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'specialty_id')) {
                $table->dropForeign(['specialty_id']);
                $table->dropColumn('specialty_id');
            }

            if (Schema::hasColumn('doctors', 'about_me')) {
                $table->dropColumn('about_me');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->foreignId('specialty_id')
                ->nullable()
                ->constrained('specialties')
                ->onDelete('set null');

            $table->text('about_me')->nullable();

        });
    }
};
