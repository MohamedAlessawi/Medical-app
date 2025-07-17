<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Services\Secretary\DoctorService;
use App\Http\Requests\Secretary\WorkingHourRequest;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    protected $doctorService;

    public function __construct(DoctorService $doctorService)
    {
        $this->doctorService = $doctorService;
    }

    public function index()
    {
        return $this->doctorService->getDoctorsInCenter();
    }

    public function show($id)
    {
        return $this->doctorService->getDoctorDetails($id);
    }

    public function getWorkingHours($id)
    {
        return $this->doctorService->getWorkingHours($id);
    }

    public function storeWorkingHour(WorkingHourRequest $request, $id)
    {
        return $this->doctorService->addWorkingHour($id, $request->validated());
    }

    public function updateWorkingHour(WorkingHourRequest $request, $id)
    {
        return $this->doctorService->updateWorkingHour($id, $request->validated());
    }

    public function deleteWorkingHour($id)
    {
        return $this->doctorService->deleteWorkingHour($id);
    }

    public function search(Request $request)
    {
        return $this->doctorService->searchDoctors($request->query('query'));
    }
}

