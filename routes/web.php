<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/check-file', function () {
    $path = base_path('google-services.json');
    return file_exists($path) ? "File found at $path" : "File NOT found at $path";
});

use App\Models\Notification;
use Google\Client;

Route::get('/test-token', function () {
    $client = new Client();
    $client->setAuthConfig(base_path('googleCredentials.json'));
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
    $token = $client->fetchAccessTokenWithAssertion();
    return dd($token);
});

Route::get('/test-push', function () {
    return \App\Models\Notification::testPush();
});
