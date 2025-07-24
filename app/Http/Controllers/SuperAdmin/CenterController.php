<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SuperAdmin\CenterService;


class CenterController extends Controller
{
    protected $centerService;

    public function __construct(CenterService $centerService)
    {
        $this->centerService = $centerService;
    }

    public function index()
    {
        return $this->centerService->listCenters();
    }

    public function show($id)
    {
        return $this->centerService->getCenterById($id);
    }

    public function update(Request $request, $id)
    {
        return $this->centerService->updateCenter($id, $request->all());
    }

    public function toggleStatus($id)
    {
        return $this->centerService->toggleCenterStatus($id);
    }
}
