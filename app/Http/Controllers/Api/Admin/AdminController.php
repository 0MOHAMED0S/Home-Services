<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Freelancer;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
public function get_freelancers(Request $request)
{
    try {
        $perPage = $request->query('per_page', 10);
        $categoryId = $request->query('category_id');

        $query = Freelancer::with(['profile.category']);

        if ($categoryId) {
            $query->whereHas('profile', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $freelancers = $query->paginate($perPage);

        return response()->json([
            'status' => 200,
            'message' => 'Freelancers retrieved successfully.',
            'data' => $freelancers
        ]);
    } catch (Exception $e) {
        Log::error('Failed to fetch freelancers: ' . $e->getMessage());

        return response()->json([
            'status' => 500,
            'message' => 'Failed to fetch freelancers.',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function get_freelancer_profile($id)
    {
        try {
            $freelancer = Freelancer::with('profile')->find($id);

            if (! $freelancer) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Freelancer not found.'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Freelancer profile retrieved successfully.',
                'data' => $freelancer
            ]);
        } catch (Exception $e) {
            Log::error('Error retrieving freelancer profile: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve freelancer profile.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function get_clients(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 10);
            $clients = User::with('profile')->paginate($perPage);

            return response()->json([
                'status' => 200,
                'message' => 'Clients retrieved successfully.',
                'data' => $clients
            ]);
        } catch (Exception $e) {
            Log::error('Failed to fetch Clients: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Failed to fetch Clients.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function get_client_profile($id)
    {
        try {
            $freelancer = Freelancer::with('profile')->find($id);

            if (! $freelancer) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Client not found.'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Client profile retrieved successfully.',
                'data' => $freelancer
            ]);
        } catch (Exception $e) {
            Log::error('Error retrieving Client profile: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Failed to retrieve Client profile.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
