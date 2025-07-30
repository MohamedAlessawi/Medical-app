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
        Schema::table('user_centers', function (Blueprint $table) {
            $table->string('condition')->nullable()->after('center_id');
            $table->date('last_visit')->nullable()->after('condition');
            $table->string('status')->nullable()->after('last_visit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_centers', function (Blueprint $table) {
            $table->dropColumn(['condition', 'last_visit', 'status']);

        });
    }
};
