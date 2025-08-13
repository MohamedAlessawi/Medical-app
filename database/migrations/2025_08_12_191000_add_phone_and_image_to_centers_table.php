<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('centers', function (Blueprint $table) {
            if (!Schema::hasColumn('centers', 'phone')) {
                $table->string('phone', 30)->nullable()->after('location');
            }
            if (!Schema::hasColumn('centers', 'image')) {
                $table->string('image')->nullable()->after('phone'); // تخزين path للصورة
            }
        });
    }

    public function down(): void {
        Schema::table('centers', function (Blueprint $table) {
            if (Schema::hasColumn('centers', 'image')) $table->dropColumn('image');
            if (Schema::hasColumn('centers', 'phone')) $table->dropColumn('phone');
        });
    }
};
