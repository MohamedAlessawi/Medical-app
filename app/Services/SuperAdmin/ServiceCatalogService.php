<?php

namespace App\Services\SuperAdmin;

use App\Models\Service;
use App\Traits\ApiResponseTrait;

class ServiceCatalogService
{
    use ApiResponseTrait;

    public function index()
    {
        return $this->unifiedResponse(true, 'Services fetched.', Service::orderBy('name')->get());
    }

    public function store(array $data)
    {
        $s = Service::create($data);
        return $this->unifiedResponse(true, 'Service created.', $s);
    }

    public function update(int $id, array $data)
    {
        $s = Service::findOrFail($id);
        $s->fill($data);
        $s->save();
        return $this->unifiedResponse(true, 'Service updated.', $s);
    }
}
