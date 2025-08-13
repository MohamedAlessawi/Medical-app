<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('center_working_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('center_id');
            $table->string('day_of_week', 16); // Saturday .. Friday
            $table->boolean('is_open')->default(true);
            $table->time('open_time')->nullable();  // nullable إذا مغلق
            $table->time('close_time')->nullable();
            $table->timestamps();

            $table->foreign('center_id')->references('id')->on('centers')->onDelete('cascade');
        });

        // تهيئة افتراضية: السبت-الخميس 09:00–17:00، الجمعة مغلق
        $days = ['Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday'];
        $centers = DB::table('centers')->pluck('id');
        foreach ($centers as $cid) {
            foreach ($days as $d) {
                DB::table('center_working_hours')->insert([
                    'center_id'  => $cid,
                    'day_of_week'=> $d,
                    'is_open'    => $d !== 'Friday',
                    'open_time'  => $d !== 'Friday' ? '09:00:00' : null,
                    'close_time' => $d !== 'Friday' ? '17:00:00' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void {
        Schema::dropIfExists('center_working_hours');
    }
};
