<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Services\Patient\RatingService;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function __construct(private RatingService $service) {}

    public function rateDoctor(Request $request)
    {
        $data = $request->validate([
            'appointment_id' => 'required|integer|exists:appointments,id',
            'score'          => 'required|numeric|min:0|max:5',
            'comment'        => 'nullable|string',
        ]);

        return $this->service->rateDoctor($data['appointment_id'], $data['score'], $data['comment'] ?? null);
    }

    public function rateCenter(Request $request)
    {
        $data = $request->validate([
            'appointment_id' => 'required|integer|exists:appointments,id',
            'score'          => 'required|numeric|min:0|max:5',
            'comment'        => 'nullable|string',
        ]);

        return $this->service->rateCenter($data['appointment_id'], $data['score'], $data['comment'] ?? null);
    }
}
