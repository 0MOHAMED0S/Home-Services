<?php

namespace App\Http\Controllers\Api\Client\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\PhoneCodeRequest;
use App\Http\Requests\Auth\PhoneLoginRequest;
use App\Http\Requests\Auth\PhoneRegisterRequest;
use App\Http\Requests\Client\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\Verify\Request as VerifyRequest;
use Vonage\Client\Exception\Request as VonageRequestException;
use Vonage\Client\Exception\Server as VonageServerException;
use Illuminate\Validation\ValidationException;

class ClientPhoneController extends Controller
{
    public function login(PhoneLoginRequest $request): JsonResponse
    {
        try {
            $user = User::where('phone', $request->phone)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid phone number or password.',
                ], 401);
            }

            $token = $user->createToken('user-token')->plainTextToken;

            return response()->json([
                'status' => 200,
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            Log::error('User Login Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'An error occurred during login.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    public function startVerification(PhoneRegisterRequest $request): JsonResponse
    {
        $phone = $request->phone;

        $basic = new Basic(
            config('services.vonage.key'),
            config('services.vonage.secret')
        );

        $client = new Client($basic);

        try {
            $verifyRequest = new VerifyRequest($phone, "HomeServices");
            $response = $client->verify()->start($verifyRequest);
            $requestId = $response->getRequestId();

            return response()->json([
                'status' => 200,
                'message' => 'Verification code sent successfully.',
                'request_id' => $requestId,
            ]);
        } catch (\Exception $e) {
            Log::error('Vonage Start Verification Error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to send verification code.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function checkVerification(PhoneCodeRequest $request): JsonResponse
    {
        $requestId = $request->request_id;
        $code = $request->code;
        $phone = $request->phone;

        $basic = new Basic(
            config('services.vonage.key'),
            config('services.vonage.secret')
        );

        $client = new Client($basic);

        try {
            $result = $client->verify()->check($requestId, $code);
            $responseData = $result->getResponseData();

            if ($responseData['status'] !== '0') {
                return response()->json([
                    'status' => 422,
                    'message' => 'Verification code incorrect or expired.'
                ], 422);
            }

            // Check if user already exists
            if (User::where('phone', $phone)->exists()) {
                return response()->json([
                    'status' => 409,
                    'message' => 'This phone number is already registered.',
                ], 409);
            }

            // Create new user
            $user = User::create([
                'phone'    => $phone,
                'name'     => $request->name,
                'password' => Hash::make($request->password),
                'provider' => 'phone'
            ]);

            $token = $user->createToken('user-token')->plainTextToken;

            return response()->json([
                'status' => 201,
                'message' => 'Account created and verified successfully.',
                'token' => $token,
                'client' => $user,
            ]);
        } catch (VonageRequestException | VonageServerException | \Exception $e) {
            Log::error('Vonage User Verification Check Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error verifying code.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->provider !== 'phone') {
                return response()->json([
                    'status' => 403,
                    'message' => 'Password change is only allowed for phone-authenticated users.',
                ], 403);
            }

            if (! Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'status' => 422,
                    'message' => 'The current password is incorrect.'
                ], 422);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'status' => 200,
                'message' => 'Password changed successfully.'
            ]);
        } catch (\Throwable $e) {
            Log::error('User Password Change Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while changing the password.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function startPasswordResetVerification(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => [
                'required',
                'regex:/^20(10|11|12|15)[0-9]{8}$/',
                'exists:users,phone',
            ]
        ]);

        $phone = $request->phone;

        $basic = new Basic(
            config('services.vonage.key'),
            config('services.vonage.secret')
        );

        $client = new Client($basic);

        try {
            $verifyRequest = new VerifyRequest($phone, "HomeServices");
            $response = $client->verify()->start($verifyRequest);
            $requestId = $response->getRequestId();

            return response()->json([
                'status' => 200,
                'message' => 'Verification code sent.',
                'request_id' => $requestId,
            ]);
        } catch (\Exception $e) {
            Log::error('Vonage Password Reset Start Error: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to send verification code.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $basic = new Basic(
            config('services.vonage.key'),
            config('services.vonage.secret')
        );

        $client = new Client($basic);

        try {
            $result = $client->verify()->check($request->request_id, $request->code);
            $data = $result->getResponseData();

            if ($data['status'] !== '0') {
                return response()->json([
                    'status' => 422,
                    'message' => 'Verification code incorrect or expired.',
                ], 422);
            }

            $user = User::where('phone', $request->phone)->first();

            if (! $user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User not found with the provided phone number.',
                ], 404);
            }

            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Password reset successfully.',
            ]);
        } catch (VonageRequestException | VonageServerException | \Exception $e) {
            Log::error('Vonage User Password Reset Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Could not reset password.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateOneSignalId(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'player_id' => 'required|string|max:255',
            ]);

            $user = $request->user(); // Authenticated user

            if (! $user) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Unauthenticated user.',
                ], 401);
            }

            $user->onesignal_id = $request->player_id;
            $user->save();

            return response()->json([
                'status'     => 200,
                'message'    => 'OneSignal ID updated successfully.',
                'player_id'  => $user->onesignal_id,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 422,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Failed to update OneSignal ID', [
                'user_id' => optional($request->user())->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Failed to update OneSignal ID.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
