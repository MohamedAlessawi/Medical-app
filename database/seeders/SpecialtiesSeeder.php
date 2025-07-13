<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecialtiesSeeder extends Seeder
{
    public function run(): void
    {
        $specialties = [
            'باطنية',
            'جراحة عامة',
            'أطفال',
            'جلدية',
            'أعصاب',
            'قلبية',
            'أسنان',
            'نسائية وتوليد',
            'أنف أذن حنجرة',
            'عظمية',
            'نفسية',
            'عيون',
            'مخ وأعصاب',
            'أورام',
            'طب طوارئ',
            'تخدير',
            'أشعة',
        ];

        foreach ($specialties as $name) {
            DB::table('specialties')->insert([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
