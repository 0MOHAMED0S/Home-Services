<?php

namespace App\Http\Controllers\Api\Freelancer\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Http\JsonResponse;

class FreelancerAcountController extends Controller
{
    public function getFreelancerAccount(): JsonResponse
    {
        try {
            $freelancer = auth('freelancer')->user();

            return response()->json([
                'status' => 200,
                'message' => 'Freelancer account fetched successfully.',
                'account' => $freelancer
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to fetch freelancer account.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
