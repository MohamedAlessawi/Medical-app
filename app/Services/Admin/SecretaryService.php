<?php

namespace App\Services\Admin;

use App\Repositories\Admin\SecretaryRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SecretaryService
{
    use ApiResponseTrait;

    public function __construct(private SecretaryRepository $repo) {}

    private function myCenterId(): int
    {
        return (int) DB::table('admin_centers')->where('user_id', Auth::id())->value('center_id');
    }

    public function index()
    {
        $data = $this->repo->listByCenterFormatted($this->myCenterId());
        return $this->unifiedResponse(true, 'Secretaries fetched.', $data);
    }

    public function update(int $userId, array $payload)
    {
        $updated = $this->repo->updateDetails($userId, $this->myCenterId(), $payload);
        if (!$updated) {
            return $this->unifiedResponse(false, 'Secretary not found for this center.', [], [], 404);
        }
        return $this->unifiedResponse(true, 'Secretary updated.', $updated);
    }

    public function toggle(int $userId, bool $isActive)
    {
        $this->repo->toggleStatus($userId, $this->myCenterId(), $isActive);
        return $this->unifiedResponse(true, 'Secretary status updated.');
    }

    public function remove(int $userId)
    {
        $this->repo->remove($userId, $this->myCenterId());
        return $this->unifiedResponse(true, 'Secretary removed from center.');
    }
}
