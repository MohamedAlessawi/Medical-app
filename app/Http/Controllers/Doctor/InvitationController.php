<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Services\Doctor\InvitationService;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $service) {}

    public function index()
    {
        return $this->service->index();
    }

    public function accept($id)
    {
        return $this->service->accept((int)$id);
    }

    public function reject($id)
    {
        return $this->service->reject((int)$id);
    }
}
