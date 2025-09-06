<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Google\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'center_id',
        'title',
        'message',
        'read_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /* -------------------- FCM helpers -------------------- */

    protected static function fcmAccessToken(): string
    {
        $candidates = [
            storage_path(env('FCM_CREDENTIALS_PATH', 'app/google/firebase-service-account.json')),
            base_path('googleCredentials.json'), // fallback لو بتستعمله محليًا
        ];
        $path = collect($candidates)->first(fn($p) => is_file($p));
        if (!$path) {
            throw new \RuntimeException('FCM service account JSON not found.');
        }

        $client = new Client();
        $client->setAuthConfig($path);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $token = $client->fetchAccessTokenWithAssertion();
        if (!isset($token['access_token'])) {
            throw new \RuntimeException('Failed to fetch FCM access token.');
        }
        return $token['access_token'];
    }

    protected static function sendFcmToToken(string $fcmToken, string $title, string $body): array
    {
        $accessToken = self::fcmAccessToken();
        $projectId   = env('FCM_PROJECT_ID', 'med-booking-system');
        $url         = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                // لا data حسب طلبك
            ]
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            // ملاحظة: شيل هدول بالإنتاج
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);
        $result   = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($result === false) {
            $err = curl_error($ch);
            curl_close($ch);
            Log::error('FCM CURL error: ' . $err);
            return ['ok' => false, 'error' => $err, 'http' => $httpCode];
        }
        curl_close($ch);

        Log::debug('FCM result: ' . $result);
        return ['ok' => $httpCode >= 200 && $httpCode < 300, 'response' => $result, 'http' => $httpCode];
    }

    /* -------------------- DB store -------------------- */

    protected static function storeDb(int $userId, ?int $centerId, string $title, string $message): self
    {
        return self::create([
            'user_id'   => $userId,
            'center_id' => $centerId,
            'title'     => $title,
            'message'   => $message,
        ]);
    }

    /* -------------------- Public API -------------------- */

    /**
     * أرسل إشعار لمستخدم واحد (تخزين DB + Push إن وجد fcm_token)
     */
    public static function pushToUser(int $userId, ?int $centerId, string $title, string $message, bool $storeInDb = true): array
    {
        $user = User::select('id','fcm_token')->find($userId);
        if (!$user) {
            return ['stored' => false, 'pushed' => false, 'reason' => 'user_not_found'];
        }

        $stored = false;
        if ($storeInDb) {
            self::storeDb($user->id, $centerId, $title, $message);
            $stored = true;
        }

        $pushed = false; $pushRes = null;
        if (!empty($user->fcm_token)) {
            $pushRes = self::sendFcmToToken($user->fcm_token, $title, $message);
            $pushed  = $pushRes['ok'] ?? false;

            // ممكن تنظّف التوكن الميت هنا لو بدك
            // if (!$pushed && isset($pushRes['response']) && str_contains($pushRes['response'], 'UNREGISTERED')) {
            //     $user->update(['fcm_token' => null]);
            // }
        }

        return ['stored' => $stored, 'pushed' => $pushed, 'push_raw' => $pushRes];
    }

    /**
     * أرسل إشعار لعدّة مستخدمين
     */
    public static function pushToUsers(array $userIds, ?int $centerId, string $title, string $message, bool $storeInDb = true): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return ['total' => 0, 'stored' => 0, 'pushed' => 0];
        }

        $users = User::whereIn('id', $userIds)->get(['id','fcm_token']);
        $storedCount = 0; $pushedCount = 0;

        foreach ($users as $u) {
            if ($storeInDb) {
                self::storeDb($u->id, $centerId, $title, $message);
                $storedCount++;
            }
            if (!empty($u->fcm_token)) {
                $res = self::sendFcmToToken($u->fcm_token, $title, $message);
                if (($res['ok'] ?? false) === true) {
                    $pushedCount++;
                }
            }
        }

        return ['total' => count($users), 'stored' => $storedCount, 'pushed' => $pushedCount];
    }

    /**
     * أرسل إشعار لكل طاقم المركز (سكرتارية + أدمن المركز)
     * + خزّنه لكل واحد منهم (وبـ center_id نفسه).
     */
    public static function feedToCenterStaff(int $centerId, string $title, string $message, bool $storeInDb = true): array
    {
        $secretaryIds = DB::table('secretaries')->where('center_id', $centerId)->pluck('user_id')->all();
        $adminIds     = DB::table('admin_centers')->where('center_id', $centerId)->pluck('user_id')->all();

        $userIds = array_values(array_unique(array_merge($secretaryIds, $adminIds)));

        return self::pushToUsers($userIds, $centerId, $title, $message, $storeInDb);
    }

    public static function testPush()
    {
        $client = new Client();
        $client->setAuthConfig(base_path('googleCredentials.json'));
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        $url = 'https://fcm.googleapis.com/v1/projects/med-booking-system/messages:send';
        $fields = [
            'message' => [
                'token' => 'e31fkE7fSEeVDTUpfYXMz0:APA91bFblB2JGiX6QlJoVI6CTekeMILZIAv_IDm8vDVb1pW6t_YI3H7EOHmoh7E-3-gJTdXLveqzXO-BUTIiq4yMPlzpxO8jmpz3a4p_zsQ3Ptn0ZtAFV1s',
                'notification' => ['title' => 'Test Push', 'body' => 'Test at ' . date('Y-m-d H:i:s')]
            ]
        ];

        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // موقت
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // موقت
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        Log::debug('Test Push Result: ' . $result);

        if ($result === false) {
            die('FCM Error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
}


// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Support\Facades\Log;
// use Google\Client;

// class Notification extends Model
// {
//     use HasFactory;

//     protected $fillable = ['user_id', 'message', 'type', 'read_at'];

//     public function user()
//     {
//         return $this->belongsTo(User::class);
//     }


//     public static function testPush()
//     {
//         $client = new Client();
//         $client->setAuthConfig(base_path('googleCredentials.json'));
//         $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
//         $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

//         $url = 'https://fcm.googleapis.com/v1/projects/med-booking-system/messages:send';
//         $fields = [
//             'message' => [
//                 'token' => 'e31fkE7fSEeVDTUpfYXMz0:APA91bFblB2JGiX6QlJoVI6CTekeMILZIAv_IDm8vDVb1pW6t_YI3H7EOHmoh7E-3-gJTdXLveqzXO-BUTIiq4yMPlzpxO8jmpz3a4p_zsQ3Ptn0ZtAFV1s',
//                 'notification' => ['title' => 'Test Push', 'body' => 'Test at ' . date('Y-m-d H:i:s')]
//             ]
//         ];

//         $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken];
//         $ch = curl_init();
//         curl_setopt($ch, CURLOPT_URL, $url);
//         curl_setopt($ch, CURLOPT_POST, true);
//         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // موقت
//         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // موقت
//         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
//         $result = curl_exec($ch);
//         Log::debug('Test Push Result: ' . $result);

//         if ($result === false) {
//             die('FCM Error: ' . curl_error($ch));
//         }
//         curl_close($ch);
//         return $result;
//     }
//}
