<?php

namespace App\Http\Controllers\Api\Freelancer\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleAuthRequest;
use App\Models\Freelancer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Two\InvalidStateException;

class FreelancerGoogleController extends Controller
{
    public function login(GoogleAuthRequest $request): JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->access_token);

            $email = $googleUser->getEmail();

            $modelsToCheck = [
                \App\Models\User::class,
            ];

            foreach ($modelsToCheck as $model) {
                if ($model::where('email', $email)->exists()) {
                    return response()->json([
                        'status' => 409,
                        'message' => 'This email is already associated with another account type.',
                    ], 409);
                }
            }

            $freelancer = Freelancer::firstOrCreate(
                ['google_id' => $googleUser->getId()],
                [
                    'name'      => $googleUser->getName(),
                    'email'     => $email,
                    'google_id' => $googleUser->getId(),
                    'provider'  => 'google',
                    'password'  => Hash::make(Str::random(10)),
                ]
            );

            $token = $freelancer->createToken('freelancer-token')->plainTextToken;

            return response()->json([
                'status' => 200,
                'message' => 'Google login successful.',
                'token' => $token,
                'freelancer' => $freelancer,
            ]);
        } catch (ClientException $e) {
            Log::warning('Invalid Google access token: ' . $e->getMessage());

            return response()->json([
                'status' => 401,
                'message' => 'Invalid or expired Google access token.',
            ], 401);
        } catch (InvalidStateException $e) {
            Log::warning('Invalid Google state: ' . $e->getMessage());

            return response()->json([
                'status' => 400,
                'message' => 'Invalid OAuth state. Please try again.',
            ], 400);
        } catch (\Throwable $e) {
            Log::error('Google Login Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Google login failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $freelancer = $request->user();

            if (! $freelancer) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Not authenticated.'
                ], 401);
            }

            $freelancer->currentAccessToken()->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Logout successful.'
            ]);
        } catch (\Throwable $e) {
            Log::error('Freelancer Logout Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'An error occurred during logout.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
