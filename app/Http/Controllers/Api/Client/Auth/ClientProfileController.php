<?php

namespace App\Http\Controllers\Api\Client\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Auth\ProfileStoreRequest;
use App\Http\Requests\Client\Auth\ProfileUpdateRequest;
use App\Models\ClientProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientProfileController extends Controller
{
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $profile = $user->profile;

            if (! $profile) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No profile found for this user.',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Client profile retrieved successfully.',
                'profile' => $profile,
                'average_rating' => $profile->average_rating,
            ]);
        } catch (\Throwable $e) {
            Log::error('Get Client Profile Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving profile.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    public function store(ProfileStoreRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if profile already exists
            if ($user->profile) {
                return response()->json([
                    'status' => 409,
                    'message' => 'Profile already exists for this client.',
                ], 409);
            }

            $data = $request->validated();
            $data['user_id'] = $user->id;

            if ($request->hasFile('path')) {
                $data['path'] = $request->file('path')->store('client_profiles', 'public');
            }

            $profile = ClientProfile::create($data);

            return response()->json([
                'status' => 201,
                'message' => 'Client profile created successfully.',
                'profile' => $profile,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Create Client Profile Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while creating the profile.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->profile;

            if (! $profile) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No profile found to update.',
                ], 404);
            }

            $data = $request->validated();

            // Handle image update
            if ($request->hasFile('path')) {
                // Delete old image if it exists
                if ($profile->path && Storage::disk('public')->exists($profile->path)) {
                    Storage::disk('public')->delete($profile->path);
                }

                $data['path'] = $request->file('path')->store('client_profiles', 'public');
            }

            $profile->update($data);

            return response()->json([
                'status' => 200,
                'message' => 'Client profile updated successfully.',
                'profile' => $profile
            ]);
        } catch (\Throwable $e) {
            Log::error('Update Client Profile Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while updating the profile.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
