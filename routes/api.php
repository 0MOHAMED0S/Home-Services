<?php

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AdminNotificationController;
use App\Http\Controllers\Api\Admin\Auth\AdminPhoneAuthController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\Client\Auth\ClientAcountController;
use App\Http\Controllers\Api\Client\Auth\ClientGoogleController;
use App\Http\Controllers\Api\Client\Auth\ClientPhoneController;
use App\Http\Controllers\Api\Client\Auth\ClientProfileController;
use App\Http\Controllers\Api\Client\Order\OrderController;
use App\Http\Controllers\Api\Freelancer\Auth\AcountController;
use App\Http\Controllers\Api\Freelancer\Auth\FreelancerAcountController;
use App\Http\Controllers\Api\Freelancer\Order\OrderController as FreelancerOrderController;
use App\Http\Controllers\Api\Freelancer\Auth\FreelancerGoogleController;
use App\Http\Controllers\Api\Freelancer\Auth\FreelancerPhoneController;
use App\Http\Controllers\Api\Freelancer\Auth\FreelancerProfileController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\FCMController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Admin
Route::prefix('admin/')->group(function () {
    Route::post('login', [AdminPhoneAuthController::class, 'login'])->middleware('guest');
    Route::middleware(['auth:sanctum', 'auth:admin'])->group(function () {
        Route::get('profile', [AdminPhoneAuthController::class, 'profile']);
        Route::post('logout', [AdminPhoneAuthController::class, 'logout']);
        Route::apiResource('categories', CategoryController::class)->except('create', 'show');
        Route::post('send-notification', [AdminNotificationController::class, 'sendNotification']);
        Route::get('/test-notification/{playerId}', function ($playerId) {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
                'Content-Type'  => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => env('ONESIGNAL_APP_ID'),
                'include_player_ids' => [$playerId],
                'headings' => ['en' => '🔔 Test Notification'],
                'contents' => ['en' => '✅ Hello! This is a test message from Laravel.'],
            ]);

            return $response->json();
        });
        Route::get('/freelancers', [AdminController::class, 'get_freelancers']);
        Route::get('/freelancers/{freelancerId}', [AdminController::class, 'get_freelancer_profile']);
        Route::get('/clients', [AdminController::class, 'get_clients']);
        Route::get('/clients/{clientId}', [AdminController::class, 'get_client_profile']);

    });
});



//Freelancer
Route::prefix('freelancer/')->middleware('throttle:20,1')->group(function () {
    Route::post('phone/resetVerification', [FreelancerPhoneController::class, 'startPasswordResetVerification']);
    Route::post('phone/resetPassword', [FreelancerPhoneController::class, 'resetPassword']);
    Route::middleware('guest')->group(function () {
        //google
        Route::post('login/google', [FreelancerGoogleController::class, 'login']);
        // Route::get('login/google/callback', [FreelancerGoogleController::class, 'handleGoogleCallback']);
        //phone
        Route::post('register/phone/start-verification', [FreelancerPhoneController::class, 'startVerification']);
        Route::post('register/phone/check-verification', [FreelancerPhoneController::class, 'checkVerification']);
        Route::post('login/phone', [FreelancerPhoneController::class, 'login']);
    });

    Route::middleware(['auth:sanctum', 'auth:freelancer'])->group(function () {
        //change password
        Route::post('phone/changePassword', [FreelancerPhoneController::class, 'changePassword'])->middleware('freelancer.phone');
        //notification
        Route::post('/update-onesignal-id', [FreelancerPhoneController::class, 'updateOneSignalId']);

        //category
        Route::get('categories', [CategoryController::class, 'index']);
        //profile
        Route::post('profile', [FreelancerProfileController::class, 'store']);
        Route::middleware(['freelancer.hasProfile'])->group(function () {
            Route::get('profile', [FreelancerProfileController::class, 'getProfile']);
            Route::put('profile', [FreelancerProfileController::class, 'update']);
            //Acount
            Route::get('/account', [FreelancerAcountController::class, 'getFreelancerAccount']);

            //order
            Route::put('/order/{order}/status', [FreelancerOrderController::class, 'freelancerUpdateStatus']);
            Route::get('/orders', [FreelancerOrderController::class, 'getFreelancerOrders']);
            Route::get('/orders/{order}', [FreelancerOrderController::class, 'showFreelancerOrder']);
            Route::get('/client/{id}/profile', [FreelancerOrderController::class, 'getClientProfileByUserId']);

            //chat
            Route::post('/messages/send', [ChatController::class, 'sendMessageAsFreelancer']);
            Route::get('/conversations', [ChatController::class, 'getFreelancerConversations']);
            Route::get('/conversations/{id}/messages', [ChatController::class, 'getFreelancerConversationMessages']);
            //rating
            Route::post('/order/{order_id}/rating', [RatingController::class, 'freelancerRateClient']);
        });
        Route::post('logout', [FreelancerGoogleController::class, 'logout']);
    });
});


//Clients
Route::prefix('client/')->middleware('throttle:20,1')->group(function () {
    Route::post('phone/resetVerification', [ClientPhoneController::class, 'startPasswordResetVerification']);
    Route::post('phone/resetPassword', [ClientPhoneController::class, 'resetPassword']);

    Route::middleware('guest')->group(function () {
        //google
        Route::post('login/google', [ClientGoogleController::class, 'login']);
        //phone
        Route::post('login/phone', [ClientPhoneController::class, 'login']);
        Route::post('register/phone/start-verification', [ClientPhoneController::class, 'startVerification']);
        Route::post('register/phone/check-verification', [ClientPhoneController::class, 'checkVerification']);
    });

    Route::middleware(['auth:sanctum', 'auth:user'])->group(function () {

        //category
        Route::get('categories', [CategoryController::class, 'index']);

        //change password
        Route::post('phone/changePassword', [ClientPhoneController::class, 'changePassword'])->middleware('client.phone');
        //notification
        Route::post('/update-onesignal-id', [ClientPhoneController::class, 'updateOneSignalId']);

        //profile
        Route::post('profile', [ClientProfileController::class, 'store']);
        Route::middleware(['client.hasProfile'])->group(function () {
            Route::get('profile', [ClientProfileController::class, 'getProfile']);
            Route::put('profile', [ClientProfileController::class, 'update']);

            //Acount
            Route::get('/account', [ClientAcountController::class, 'getClientAccount']);
            //order
            Route::post('/{category_id}/order', [OrderController::class, 'store']);
            Route::put('/orders/{order}', [OrderController::class, 'Update']);
            Route::put('/orders/{order}/status', [OrderController::class, 'clientUpdateStatus']);
            Route::get('/{category_id}/freelancers', [OrderController::class, 'getByCategory']);
            Route::get('/orders', [OrderController::class, 'getOrders']);
            Route::get('/orders/{order}', [OrderController::class, 'show']);
            Route::get('/freelancer/{id}/profile', [OrderController::class, 'getProfileById']);

            //chat
            Route::post('/messages/send', [ChatController::class, 'sendMessageAsUser']);
            Route::get('/conversations', [ChatController::class, 'getUserConversations']);
            Route::get('/conversations/{id}/messages', [ChatController::class, 'getUserConversationMessages']);
            //rating
            Route::post('/order/{order_id}/rating', [RatingController::class, 'userRateFreelancer']);
        });
        Route::post('logout', [ClientGoogleController::class, 'logout']);
    });
});
