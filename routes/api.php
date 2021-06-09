<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('send/invitation', 'API\AuthController@AdminSendInvitation');
Route::post('register', 'API\AuthController@UserRegister');
Route::post('check/otp', 'API\AuthController@CheckOTP');
Route::post('login', 'API\AuthController@UserLogin');

Route::group(['middleware' => 'auth:api', 'namespace' => 'API', 'prefix' => 'user'], function(){

    Route::post('profile/update', 'AuthController@UserProfileUpdate');
});