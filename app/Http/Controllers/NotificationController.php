<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $service) {}

    public function SercretaryNotification()
    {
        return $this->service->secretaryIndex();
    }

    public function doctorNotification()
    {
        return $this->service->doctorIndex();
    }

    public function patientNotification()
    {
        return $this->service->patientIndex();
    }
}
