<?php

namespace App\Services\patient;

use App\Repositories\Patient\PatientProfileRepository;
use App\Models\PatientProfile;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class PatientProfileService
{
    use ApiResponseTrait;

    protected $repository;

    public function __construct(PatientProfileRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getFullProfile($userId)
    {
        $profile = PatientProfile::where('user_id', $userId)->first();

        if (!$profile) {
            return $this->unifiedResponse(false, 'Patient profile not found.', [], [], 404);
        }

        return $this->unifiedResponse(true, 'Patient profile fetched successfully.', [
            'personal' => [
                'contact_info' => $this->repository->getContactInfo($userId),
                'personal_details' => $this->repository->getPersonalDetails($userId),
            ],
            'medical' => [
                'condition' => $profile->condition,
                'medical_history' => $profile->medical_history,
                'allergies' => $profile->allergies,
                'current_medications' => $profile->current_medications,
                'family_medical_history' => $profile->family_medical_history,
                'blood_type' => $profile->blood_type,
                'height' => $profile->height,
                'weight' => $profile->weight,
                'bmi' => $profile->bmi,
                'bmi_category' => $profile->bmi_category,
                'age' => $profile->age,
            ],
            'emergency_contacts' => [
                'emergency_contact_name' => $profile->emergency_contact_name,
                'emergency_contact_phone' => $profile->emergency_contact_phone,
            ],
            'lifestyle' => [
                'smoking_status' => $profile->smoking_status,
                'alcohol_consumption' => $profile->alcohol_consumption,
                'lifestyle_notes' => $profile->lifestyle_notes,
            ],
            'follow_up' => [
                'last_visit' => $profile->last_visit_formatted,
                'next_follow_up' => $profile->next_follow_up_formatted,
                'treatment_notes' => $profile->treatment_notes,
            ],
            'insurance' => [
                'provider' => $profile->insurance_provider,
                'number' => $profile->insurance_number,
                'expiry' => $profile->insurance_expiry_formatted,
                'is_expired' => $profile->insurance_expiry ? $profile->insurance_expiry->isPast() : false,
            ],
            'appointments' => [
                'upcoming' => $this->repository->getUpcomingAppointments($userId),
                'old' => $this->repository->getOldAppointments($userId),
            ],
            'medical_reports' => $this->repository->getMedicalReports($userId),
        ]);
    }

    public function updateProfile($userId, array $data)
    {
        $profile = PatientProfile::where('user_id', $userId)->first();

        if (!$profile) {
            $profile = PatientProfile::create([
                'user_id' => $userId,
                'status' => 'active'
            ]);
        }

        $profile->update($data);

        return $this->unifiedResponse(true, 'Patient profile updated successfully.', $profile);
    }

    public function updateMedicalInfo($userId, array $data)
    {
        $profile = PatientProfile::where('user_id', $userId)->first();

        if (!$profile) {
            return $this->unifiedResponse(false, 'Patient profile not found.', [], [], 404);
        }

        $medicalFields = [
            'condition', 'medical_history', 'allergies', 'current_medications',
            'family_medical_history', 'blood_type', 'height', 'weight'
        ];

        $medicalData = array_intersect_key($data, array_flip($medicalFields));
        $profile->update($medicalData);

        return $this->unifiedResponse(true, 'Medical information updated successfully.', $profile);
    }

    public function updateEmergencyContacts($userId, array $data)
    {
        $profile = PatientProfile::where('user_id', $userId)->first();

        if (!$profile) {
            return $this->unifiedResponse(false, 'Patient profile not found.', [], [], 404);
        }

        $emergencyFields = ['emergency_contact_name', 'emergency_contact_phone'];
        $emergencyData = array_intersect_key($data, array_flip($emergencyFields));
        $profile->update($emergencyData);

        return $this->unifiedResponse(true, 'Emergency contacts updated successfully.', $profile);
    }

    public function updateLifestyleInfo($userId, array $data)
    {
        $profile = PatientProfile::where('user_id', $userId)->first();

        if (!$profile) {
            return $this->unifiedResponse(false, 'Patient profile not found.', [], [], 404);
        }

        $lifestyleFields = ['smoking_status', 'alcohol_consumption', 'lifestyle_notes'];
        $lifestyleData = array_intersect_key($data, array_flip($lifestyleFields));
        $profile->update($lifestyleData);

        return $this->unifiedResponse(true, 'Lifestyle information updated successfully.', $profile);
    }

    public function updateInsuranceInfo($userId, array $data)
    {
        $profile = PatientProfile::where('user_id', $userId)->first();

        if (!$profile) {
            return $this->unifiedResponse(false, 'Patient profile not found.', [], [], 404);
        }

        $insuranceFields = ['insurance_provider', 'insurance_number', 'insurance_expiry'];
        $insuranceData = array_intersect_key($data, array_flip($insuranceFields));
        $profile->update($insuranceData);

        return $this->unifiedResponse(true, 'Insurance information updated successfully.', $profile);
    }

    public function getPatientsNeedingFollowUp()
    {
        $patients = PatientProfile::needsFollowUp()
            ->with('user')
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'patient_name' => $profile->user->full_name,
                    'next_follow_up' => $profile->next_follow_up_formatted,
                    'days_until_follow_up' => $profile->next_follow_up ? $profile->next_follow_up->diffInDays(now()) : null,
                ];
            });

        return $this->unifiedResponse(true, 'Patients needing follow-up fetched successfully.', $patients);
    }

    public function getPatientsWithExpiredInsurance()
    {
        $patients = PatientProfile::withExpiredInsurance()
            ->with('user')
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'user_id' => $profile->user_id,
                    'patient_name' => $profile->user->full_name,
                    'insurance_provider' => $profile->insurance_provider,
                    'insurance_expiry' => $profile->insurance_expiry_formatted,
                    'days_since_expiry' => $profile->insurance_expiry ? $profile->insurance_expiry->diffInDays(now()) : null,
                ];
            });

        return $this->unifiedResponse(true, 'Patients with expired insurance fetched successfully.', $patients);
    }
}
