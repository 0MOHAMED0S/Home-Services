<?php

namespace App\Http\Controllers\Api\Client\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleAuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Two\InvalidStateException;

class ClientGoogleController extends Controller
{
    public function login(GoogleAuthRequest $request): JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->userFromToken($request->access_token);

            $email = $googleUser->getEmail();

            $emailExistsInOtherGuards =
                \App\Models\Freelancer::where('email', $email)->exists();
            if ($emailExistsInOtherGuards) {
                return response()->json([
                    'status' => 409,
                    'message' => 'This email is already associated with another account type.',
                ], 409);
            }

            $user = User::firstOrCreate(
                ['google_id' => $googleUser->getId()],
                [
                    'name'      => $googleUser->getName(),
                    'email'     => $email,
                    'google_id' => $googleUser->getId(),
                    'provider'  => 'google',
                    'password'  => Hash::make(Str::random(10)),
                ]
            );

            $token = $user->createToken('user-token')->plainTextToken;

            return response()->json([
                'status' => 200,
                'message' => 'Google login successful.',
                'token' => $token,
                'client' => $user,
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

    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Not authenticated.',
                ], 401);
            }

            $user->currentAccessToken()->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Logout successful.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Client Logout Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'An error occurred during logout.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
