<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PatientProfile;
use App\Models\User;

class PatientProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch users who have the patient role
        $patients = User::whereHas('roles', function ($query) {
            $query->where('name', 'patient');
        })->get();

        foreach ($patients as $patient) {
            PatientProfile::create([
                'user_id' => $patient->id,
                'condition' => $this->getRandomCondition(),
                'medical_history' => $this->getRandomMedicalHistory(),
                'allergies' => $this->getRandomAllergies(),
                'current_medications' => $this->getRandomMedications(),
                'family_medical_history' => $this->getRandomFamilyHistory(),
                'blood_type' => $this->getRandomBloodType(),
                'height' => rand(150, 190),
                'weight' => rand(50, 100),
                'emergency_contact_name' => fake('ar_SA')->name(),
                'emergency_contact_phone' => fake()->phoneNumber(),
                'smoking_status' => $this->getRandomSmokingStatus(),
                'alcohol_consumption' => $this->getRandomAlcoholConsumption(),
                'lifestyle_notes' => $this->getRandomLifestyleNotes(),
                'last_visit' => fake()->dateTimeBetween('-6 months', 'now'),
                'next_follow_up' => fake()->dateTimeBetween('now', '+3 months'),
                'treatment_notes' => $this->getRandomTreatmentNotes(),
                'preferred_language' => 'ar',
                'insurance_provider' => $this->getRandomInsuranceProvider(),
                'insurance_number' => 'INS-' . fake()->numberBetween(10000, 99999),
                'insurance_expiry' => fake()->dateTimeBetween('now', '+2 years'),
                'status' => 'active',
            ]);
        }
    }

    private function getRandomCondition(): string
    {
        $conditions = [
            'Good health',
            'Hypertension',
            'Diabetes',
            'Heart disease',
            'Respiratory disease',
            'Gastrointestinal disease',
            'Dermatological disease',
            'Bone disease',
        ];
        return $conditions[array_rand($conditions)];
    }

    private function getRandomMedicalHistory(): string
    {
        $histories = [
            'Abdominal surgery 5 years ago',
            'Leg fracture 3 years ago',
            'Heart attack 2 years ago',
            'No significant medical history',
            'Multiple surgeries',
            'Chronic illnesses since childhood',
        ];
        return $histories[array_rand($histories)];
    }

    private function getRandomAllergies(): string
    {
        $allergies = [
            'No known allergies',
            'Penicillin allergy',
            'Dust allergy',
            'Food allergies',
            'Animal allergies',
            'Fragrance allergies',
        ];
        return $allergies[array_rand($allergies)];
    }

    private function getRandomMedications(): string
    {
        $medications = [
            'No current medications',
            'Antihypertensive drugs',
            'Diabetes medications',
            'Cardiac medications',
            'Daily vitamins',
            'Pain relievers as needed',
        ];
        return $medications[array_rand($medications)];
    }

    private function getRandomFamilyHistory(): string
    {
        $histories = [
            'Family history of heart disease',
            'Family history of diabetes',
            'Family history of cancer',
            'Hereditary diseases',
            'No significant family history',
            'Family history of hypertension',
        ];
        return $histories[array_rand($histories)];
    }

    private function getRandomBloodType(): string
    {
        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        return $bloodTypes[array_rand($bloodTypes)];
    }

    private function getRandomSmokingStatus(): string
    {
        $statuses = ['non_smoker', 'former_smoker', 'current_smoker'];
        return $statuses[array_rand($statuses)];
    }

    private function getRandomAlcoholConsumption(): string
    {
        $consumptions = ['none', 'occasional', 'moderate', 'heavy'];
        return $consumptions[array_rand($consumptions)];
    }

    private function getRandomLifestyleNotes(): string
    {
        $notes = [
            'Regular exercise',
            'Healthy diet',
            'Low physical activity',
            'High stress',
            'Regular sleep',
            'Good health habits',
        ];
        return $notes[array_rand($notes)];
    }

    private function getRandomTreatmentNotes(): string
    {
        $notes = [
            'Routine follow-up every 3 months',
            'Regular check-ups',
            'Physiotherapy',
            'Continuous medication',
            'Follow-up with a specialist',
            'No current treatment',
        ];
        return $notes[array_rand($notes)];
    }

    private function getRandomInsuranceProvider(): string
    {
        $providers = [
            'National Insurance Co.',
            'Cooperative Insurance Co.',
            'Arabia Insurance Co.',
            'Gulf Insurance Co.',
            'Islamic Insurance Co.',
            'Saudi Insurance Co.',
        ];
        return $providers[array_rand($providers)];
    }
}
