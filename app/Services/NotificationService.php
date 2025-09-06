<?php

namespace App\Services;

use App\Models\Notification;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    use ApiResponseTrait;

    /**
     * سكرتارية: عرض إشعارات المركز (Center feed).
     * يعتمد على أول center_id للسكرتيرة.
     */
    public function secretaryIndex()
    {
        $centerId = optional(Auth::user()->secretaries->first())->center_id;

        if (!$centerId) {
            return $this->unifiedResponse(true, 'Notifications fetched.', collect());
        }

        $data = Notification::where('center_id', $centerId)
            // ->whereNull('user_id')               // إشعارات عامة للمركز
            ->orderByDesc('created_at')
            ->get(['id','center_id','title','message','created_at']);

        return $this->unifiedResponse(true, 'Notifications fetched.', $data);
    }

    /**
     * طبيب: إشعارات موجّهة مباشرة للطبيب (user_id = auth).
     */
    public function doctorIndex()
    {
        $uid = Auth::id();

        $data = Notification::where('user_id', $uid)
            ->orderByDesc('created_at')
            ->get(['id','center_id','title','message','created_at']);

        return $this->unifiedResponse(true, 'Notifications fetched.', $data);
    }

    /**
     * مريض: إشعارات موجّهة مباشرة للمريض (user_id = auth).
     */
    public function patientIndex()
    {
        $uid = Auth::id();

        $data = Notification::where('user_id', $uid)
            ->orderByDesc('created_at')
            ->get(['id','center_id','title','message','created_at']);

        return $this->unifiedResponse(true, 'Notifications fetched.', $data);
    }
}

// namespace App\Services;

// use Kreait\Firebase\Messaging\CloudMessage;
// use Kreait\Firebase\Messaging\Notification;
// use Kreait\Firebase\Contract\Messaging;
// use App\Traits\ApiResponseTrait;


// class NotificationService
// {
//     protected $messaging;

//     public function __construct(Messaging $messaging)
//     {
//         $this->messaging = $messaging;
//     }
//
    // public function sendNotification(string $fcmToken, string $title, string $body, array $data = [])
    // {
    //     if (!$fcmToken) {
    //         return false;
    //     }

    //     $message = CloudMessage::withTarget('token', $fcmToken)
    //         ->withNotification(Notification::create($title, $body))
    //         ->withData($data);

    //     $this->messaging->send($message);

    //     return true;
    // }
// }
