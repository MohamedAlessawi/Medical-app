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
        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->text('medical_history')->nullable()->comment('Patient medical history');
            $table->text('allergies')->nullable()->comment('Known allergies');
            $table->text('current_medications')->nullable()->comment('Current medications');
            $table->text('family_medical_history')->nullable()->comment('Family medical history');

            $table->string('blood_type')->nullable()->comment('Blood type');
            $table->decimal('height', 5, 2)->nullable()->comment('Height in centimeters');
            $table->decimal('weight', 5, 2)->nullable()->comment('Weight in kilograms');
            $table->string('emergency_contact_name')->nullable()->comment('Emergency contact name');
            $table->string('emergency_contact_phone')->nullable()->comment('Emergency contact phone');

            $table->enum('smoking_status', ['non_smoker', 'former_smoker', 'current_smoker'])->nullable();
            $table->enum('alcohol_consumption', ['none', 'occasional', 'moderate', 'heavy'])->nullable();
            $table->text('lifestyle_notes')->nullable()->comment('Lifestyle notes');

            $table->date('next_follow_up')->nullable()->comment('Next follow-up date');
            $table->text('treatment_notes')->nullable()->comment('Treatment notes');
            $table->string('preferred_language')->default('ar')->comment('Preferred communication language');

            $table->string('insurance_provider')->nullable()->comment('Health insurance provider');
            $table->string('insurance_number')->nullable()->comment('Insurance number');
            $table->date('insurance_expiry')->nullable()->comment('Insurance expiry date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'medical_history',
                'allergies',
                'current_medications',
                'family_medical_history',
                'blood_type',
                'height',
                'weight',
                'emergency_contact_name',
                'emergency_contact_phone',
                'smoking_status',
                'alcohol_consumption',
                'lifestyle_notes',
                'next_follow_up',
                'treatment_notes',
                'preferred_language',
                'insurance_provider',
                'insurance_number',
                'insurance_expiry'
            ]);
        });
    }
};
