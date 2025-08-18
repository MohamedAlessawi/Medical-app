<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('secretaries', function (Blueprint $table) {
            if (!Schema::hasColumn('secretaries', 'shift')) {
                $table->enum('shift', ['morning','evening','night'])
                      ->default('morning')
                      ->after('center_id');
            }
        });

        DB::table('secretaries')->whereNull('shift')->update(['shift' => 'morning']);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('secretaries', function (Blueprint $table) {
            if (Schema::hasColumn('secretaries', 'shift')) {
                $table->dropColumn('shift');
            }
        });
    }
};
