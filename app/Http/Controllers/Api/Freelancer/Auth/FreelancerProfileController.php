<?php

namespace App\Http\Controllers\Api\Freelancer\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Freelancer\Auth\ProfileStoreRequest;
use App\Http\Requests\Freelancer\Auth\ProfileUpdateRequest;
use Illuminate\Http\Request;
use App\Models\FreelancerProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class FreelancerProfileController extends Controller
{
    public function getProfile(Request $request): JsonResponse
    {
        try {
            $freelancer = $request->user();
            $profile = $freelancer->profile;

            if (! $profile) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No profile found for this freelancer.',
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Freelancer profile retrieved successfully.',
                'profile' => $profile,
                'average_rating' => $profile->average_rating,
            ]);
        } catch (\Throwable $e) {
            Log::error('Get Freelancer Profile Error: ' . $e->getMessage());

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
            $freelancer = $request->user();
            if ($freelancer->profile) {
                return response()->json([
                    'status' => 409,
                    'message' => 'Profile already exists for this freelancer.',
                ], 409);
            }
            $data = $request->validated();
            $data['freelancer_id'] = $freelancer->id;

            if ($request->hasFile('path')) {
                $data['path'] = $request->file('path')->store('freelancer_profiles', 'public');
            }

            $profile = FreelancerProfile::create($data);

            return response()->json([
                'status' => 201,
                'message' => 'Freelancer profile created successfully.',
                'profile' => $profile
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Create Freelancer Profile Error: ' . $e->getMessage());

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
            $freelancer = $request->user();
            $profile = $freelancer->profile;

            if (! $profile) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No profile found to update.',
                ], 404);
            }

            $data = $request->validated();

            // Handle image
            if ($request->hasFile('path')) {
                if ($profile->path && Storage::disk('public')->exists($profile->path)) {
                    Storage::disk('public')->delete($profile->path);
                }

                $data['path'] = $request->file('path')->store('freelancer_profiles', 'public');
            }

            $profile->update($data);

            return response()->json([
                'status' => 200,
                'message' => 'Freelancer profile updated successfully.',
                'profile' => $profile
            ]);
        } catch (\Throwable $e) {
            Log::error('Update Freelancer Profile Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while updating the profile.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
