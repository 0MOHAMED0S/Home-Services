<?php

namespace App\Http\Controllers\Api\Client\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Http\JsonResponse;

class ClientAcountController extends Controller
{
    public function getClientAccount(): JsonResponse
    {
        try {
            $user = auth('user')->user();

            return response()->json([
                'status' => 200,
                'message' => 'Client account fetched successfully.',
                'account' => $user
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
