<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PhoneLoginRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminPhoneAuthController extends Controller
{
    public function login(PhoneLoginRequest $request): JsonResponse
    {
        try {
            $admin = Admin::where('phone', $request->phone)->first();

            if (! $admin || ! Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid phone number or password.'
                ], 401);
            }

            $token = $admin->createToken('admin-token')->plainTextToken;

            return response()->json([
                'status' => 200,
                'message' => 'Login successful.',
                'token' => $token,
                'admin' => $admin
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Admin Login Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'An error occurred during login. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();

            if (! $admin) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Not authenticated.'
                ], 401);
            }

            $admin->currentAccessToken()->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Logout successful.'
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Admin Logout Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'An error occurred during logout.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function profile(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();

            if (! $admin) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Not authenticated.'
                ], 401);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Admin profile fetched successfully.',
                'admin' => $admin
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Admin Profile Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while fetching the profile.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
