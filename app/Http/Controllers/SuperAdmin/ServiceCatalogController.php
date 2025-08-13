<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\SuperAdmin\ServiceCatalogService;
use Illuminate\Http\Request;

class ServiceCatalogController extends Controller
{
    public function __construct(private ServiceCatalogService $service) {}

    public function index() { return $this->service->index(); }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => ['required','string','max:255','unique:services,name'],
            'description' => ['nullable','string'],
            'is_active' => ['nullable','boolean'],
        ]);
        $data['is_active'] = $data['is_active'] ?? true;
        return $this->service->store($data);
    }

    public function update($id, Request $r)
    {
        $data = $r->validate([
            'name' => ['sometimes','string','max:255','unique:services,name,'.$id],
            'description' => ['sometimes','nullable','string'],
            'is_active' => ['sometimes','boolean'],
        ]);
        return $this->service->update((int)$id, $data);
    }
}
