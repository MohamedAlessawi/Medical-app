<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCenterRequest;
use App\Http\Requests\Admin\UploadCenterImageRequest;
use App\Http\Requests\Admin\UpdateCenterWorkingHoursRequest;
use App\Services\Admin\CenterService;

class CenterController extends Controller
{
    public function __construct(private CenterService $service) {}

    public function show() { return $this->service->show(); }

    public function update(UpdateCenterRequest $r)
    {
        return $this->service->update($r->validated());
    }

    public function uploadImage(UploadCenterImageRequest $r)
    {
        return $this->service->uploadImage($r->file('image'));
    }

    public function workingHoursIndex()
    {
        return $this->service->workingHoursIndex();
    }

    public function workingHoursBulkUpdate(UpdateCenterWorkingHoursRequest $r)
    {
        return $this->service->workingHoursBulkUpdate($r->validated()['items']);
    }
}
