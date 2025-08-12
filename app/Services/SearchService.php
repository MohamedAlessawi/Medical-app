<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\Center;
use App\Models\Specialty;
use App\Models\DoctorProfile;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchService
{
    use ApiResponseTrait;

    public function search($query)
    {
        if (empty($query) || strlen($query) < 2) {
            return $this->unifiedResponse(false, 'Search query must be at least 2 characters long.', [], [], 400);
        }

        $searchTerm = '%' . $query . '%';


        $specialties = Specialty::where('name', 'LIKE', $searchTerm)
            ->get();


        $doctors = Doctor::with(['user', 'doctorProfile', 'specialty'])
            ->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', $searchTerm)
                  ->orWhere('email', 'LIKE', $searchTerm);
            })
            ->orWhereHas('doctorProfile', function ($q) use ($searchTerm) {
                $q->where('bio', 'LIKE', $searchTerm)
                  ->orWhere('experience_years', 'LIKE', $searchTerm);
            })
            ->orWhereHas('specialty', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', $searchTerm)
                  ->orWhere('name_ar', 'LIKE', $searchTerm);
            })
            ->get();


        $centers = Center::where('name', 'LIKE', $searchTerm)
            ->orWhere('location', 'LIKE', $searchTerm)
            ->get();


        $results = [
            'specialties' => $specialties->map(function ($specialty) {
                return [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'doctors_count' => $specialty->doctors_count,
                    'type' => 'specialty'
                ];
            }),
            'doctors' => $doctors->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                    'email' => $doctor->user->email,
                    'specialty' => $doctor->specialty ? $doctor->specialty->name : null,
                    'bio' => $doctor->doctorProfile ? $doctor->doctorProfile->about_me : null,
                    'experience_years' => $doctor->doctorProfile ? $doctor->doctorProfile->years_of_experience : null,
                    'type' => 'doctor'
                ];
            }),
            'centers' => $centers->map(function ($center) {
                return [
                    'id' => $center->id,
                    'name' => $center->name,
                    'location' => $center->location,
                    'rating' => $center->rating,
                    'type' => 'center'
                ];
            })
        ];


        $stats = [
            'total_results' => $specialties->count() + $doctors->count() + $centers->count(),
            'specialties_count' => $specialties->count(),
            'doctors_count' => $doctors->count(),
            'centers_count' => $centers->count()
        ];

        return $this->unifiedResponse(true, 'Search completed successfully.', $results, [], 200);
    }

    public function advancedSearch($request)
    {
        $query = $request->get('query', '');
        $type = $request->get('type', 'all'); // all, doctors, centers, specialties
        $limit = $request->get('limit', 20);

        if (empty($query) || strlen($query) < 2) {
            return $this->unifiedResponse(false, 'Search query must be at least 2 characters long.', [], [], 400);
        }

        $searchTerm = '%' . $query . '%';
        $results = [];

        if ($type === 'all' || $type === 'specialties') {
            $specialties = Specialty::where('name', 'LIKE', $searchTerm)
                ->limit($limit)
                ->get();

            $results['specialties'] = $specialties->map(function ($specialty) {
                return [
                    'id' => $specialty->id,
                    'name' => $specialty->name,
                    'doctors_count' => $specialty->doctors_count,
                    'type' => 'specialty'
                ];
            });
        }

        if ($type === 'all' || $type === 'doctors') {
            $doctors = Doctor::with(['user', 'doctorProfile', 'specialty'])
                ->whereHas('user', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', $searchTerm)
                      ->orWhere('email', 'LIKE', $searchTerm);
                })
                ->orWhereHas('doctorProfile', function ($q) use ($searchTerm) {
                    $q->where('about_me', 'LIKE', $searchTerm)
                      ->orWhere('years_of_experience', 'LIKE', $searchTerm);
                })
                ->orWhereHas('specialty', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', $searchTerm);
                })
                ->limit($limit)
                ->get();

            $results['doctors'] = $doctors->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user->name,
                    'email' => $doctor->user->email,
                    'specialty' => $doctor->specialty ? $doctor->specialty->name : null,
                    'bio' => $doctor->doctorProfile ? $doctor->doctorProfile->about_me : null,
                    'experience_years' => $doctor->doctorProfile ? $doctor->doctorProfile->years_of_experience : null,
                    'type' => 'doctor'
                ];
            });
        }

        if ($type === 'all' || $type === 'centers') {
            $centers = Center::where('name', 'LIKE', $searchTerm)
                ->orWhere('location', 'LIKE', $searchTerm)
                ->limit($limit)
                ->get();

            $results['centers'] = $centers->map(function ($center) {
                return [
                    'id' => $center->id,
                    'name' => $center->name,
                    'location' => $center->location,
                    'rating' => $center->rating,
                    'type' => 'center'
                ];
            });
        }

        
        $stats = [
            'total_results' => array_sum(array_map('count', $results)),
            'specialties_count' => isset($results['specialties']) ? $results['specialties']->count() : 0,
            'doctors_count' => isset($results['doctors']) ? $results['doctors']->count() : 0,
            'centers_count' => isset($results['centers']) ? $results['centers']->count() : 0
        ];

        return $this->unifiedResponse(true, 'Advanced search completed successfully.', $results, $stats, 200);
    }
}
