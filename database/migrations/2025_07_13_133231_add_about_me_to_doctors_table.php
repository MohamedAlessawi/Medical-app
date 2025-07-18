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
        $table->text('about_me')->nullable();
    });
}

public function down(): void
{
    Schema::table('doctors', function (Blueprint $table) {
        $table->dropColumn('about_me');
    });
}

};
