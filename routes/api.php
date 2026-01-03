<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CameraAccessController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::Group([
    'middleware' => 'api',
], function ($router) {
    Route::post('/camera/info/{id}', [CameraController::class, 'getCameraInfo']);
});

// User camera routes (authenticated users)
Route::group([
    'middleware' => ['api', 'auth:api'],
    'prefix' => 'cameras'
], function ($router) {
    Route::get('/', [CameraController::class, 'getUserCameras']);
});

Route::group([
    'middleware' => ['api','throttle:30,1'],
    'prefix' => 'auth'
], function ($router) {

    Route::get('hello', [AuthController::class, 'hello']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('user-profile', [AuthController::class, 'userProfile']);
        Route::post('change-pass', [AuthController::class, 'changePassWord']);
        Route::post('upload-avatar', [AuthController::class, 'uploadAvatar']);
        Route::get('avatars/{filename}', [AuthController::class, 'getAvatar']);
    });
});

Route::group([
    'middleware' => ['api', 'auth:api', 'throttle:100,0.25'],
    'prefix' => 'product',
], function ($router) {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{product}', [ProductController::class, 'show']);
    Route::put('/{product}', [ProductController::class, 'update']);
    Route::delete('/{product}', [ProductController::class, 'destroy']);
});

 Route::group([
     'middleware' => ['api', 'auth:api', 'admin', 'throttle:100,1'],
     'prefix' => 'admin'
 ], function ($router) {
     Route::get('/users', [AdminController::class, 'listUsers']);
     Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
     Route::get('/deleted-users',[AdminController::class, 'listSoftDeletedUsers']);
     Route::post('/restore-user/{id}', [AdminController::class, 'restoreUser']);
     Route::get('/cameras', [CameraController::class, 'index']);
     Route::post('/cameras/', [CameraController::class, 'store']);
     Route::get('/cameras/list-active',[CameraController::class, 'listActiveCameras']);
     Route::get('/cameras/list',[CameraController::class, 'fetchActiveCameras']);
     Route::get('/cameras/{id}', [CameraController::class, 'show']);
     Route::get('/cameras/{id}/info', [CameraController::class, 'getCameraInfo']);
     Route::put('/cameras/{camera}', [CameraController::class, 'update']);
     Route::delete('/cameras/{camera}', [CameraController::class, 'destroy']);
     Route::post('/cameras/toggle/{id}', [CameraController::class, 'toggleOnOff']);
     Route::post('/cameras/init', [CameraController::class, 'initCamera']);
     Route::post('/cameras/streams/stop/{id}', [CameraController::class, 'shutdownStream']);

     // Detection routes
     Route::post('/cameras/{id}/detection', [CameraController::class, 'updateDetectionSettings']);
     Route::get('/cameras/{id}/detection/status', [CameraController::class, 'getDetectionStatus']);

     // Recording routes
     Route::get('/cameras/{id}/recordings', [CameraController::class, 'getRecordings']);

     // Thumbnail routes
     Route::get('/cameras/{id}/thumbnails', [CameraController::class, 'getThumbnails']);
     Route::post('/cameras/{id}/thumbnail/capture', [CameraController::class, 'captureThumbnail']);

     // Share token routes (admin only)
     Route::get('/share-tokens/all', [CameraController::class, 'getAllShareTokens']);
     Route::post('/cameras/{id}/share', [CameraController::class, 'generateShareToken']);
     Route::get('/cameras/{id}/share-tokens', [CameraController::class, 'getShareTokens']);
     Route::delete('/cameras/{cameraId}/share-tokens/{tokenId}', [CameraController::class, 'revokeShareToken']);

     // Camera user access routes (admin only)
     Route::get('/cameras/{cameraId}/access', [CameraAccessController::class, 'getCameraAccessList']);
     Route::post('/cameras/{cameraId}/access', [CameraAccessController::class, 'grantAccess']);
     Route::put('/cameras/{cameraId}/access/{accessId}', [CameraAccessController::class, 'updateAccess']);
     Route::delete('/cameras/{cameraId}/access/{accessId}', [CameraAccessController::class, 'revokeAccess']);

     // Get all users for dropdown (non-admin users)
     Route::get('/users/available', [AdminController::class, 'getAvailableUsers']);
 });

// Public share token validation (no auth required) - placed outside all auth middleware groups
Route::withoutMiddleware(['auth:api', 'auth'])->group(function () {
    Route::get('/share/validate/{token}', [CameraController::class, 'validateShareToken']);
});
