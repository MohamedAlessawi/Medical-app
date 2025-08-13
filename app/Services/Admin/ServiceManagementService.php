<?php

namespace App\Services\Admin;

use App\Repositories\Admin\ServiceRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceManagementService
{
    use ApiResponseTrait;

    public function __construct(private ServiceRepository $repo) {}

    private function myCenterId(): int
    {
        return (int) DB::table('admin_centers')->where('user_id', Auth::id())->value('center_id');
    }

    public function catalog()
    {
        return $this->unifiedResponse(true, 'Catalog fetched.', $this->repo->catalog());
    }

    public function centerServices()
    {
        return $this->unifiedResponse(true, 'Center services fetched.', $this->repo->centerServices($this->myCenterId()));
    }

    public function add(array $data)
    {
        $link = $this->repo->addToCenter($this->myCenterId(), $data['service_id']);
        return $this->unifiedResponse(true, 'Service added to center.', $link);
    }

    public function remove(int $id)
    {
        $this->repo->removeCenterService($id);
        return $this->unifiedResponse(true, 'Service removed from center.');
    }
}
