<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RatingController extends Controller
{
    public function freelancerRateClient(Request $request, $orderId): JsonResponse
    {
        try {
            // Validate input
            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'nullable|string|max:1000',
            ]);

            $freelancer = $request->user();

            // Ensure the order belongs to the freelancer
            $order = Order::with('ratings')
                ->where('id', $orderId)
                ->where('freelancer_id', $freelancer->id)
                ->first();

            if (! $order) {
                return response()->json([
                    'message' => 'Order not found or does not belong to you.'
                ], 404);
            }

            // Ensure order is complete
            if ($order->status !== 'complete') {
                return response()->json([
                    'message' => 'You can only rate after the order is complete.'
                ], 400);
            }

            // Check if already rated
            $existingRating = Rating::where('order_id', $orderId)
                ->where('rated_by', 'freelancer')
                ->first();

            if ($existingRating) {
                return response()->json([
                    'message' => 'You have already rated this order.'
                ], 409);
            }

            // Create the rating
            $rating = Rating::create([
                'order_id'   => $orderId,
                'rated_by'   => 'freelancer',
                'rater_id'   => $freelancer->id,
                'ratee_id'   => $order->user_id,
                'rating'     => $request->rating,
                'review'     => $request->review,
            ]);

            return response()->json([
                'message' => 'Client rated successfully.',
                'data'    => $rating
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to rate client: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred while submitting the rating.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    public function userRateFreelancer(Request $request, $orderId): JsonResponse
    {
        try {
            // Validate input
            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'review' => 'nullable|string|max:1000',
            ]);

            $user = $request->user();

            // Ensure the order belongs to the user
            $order = Order::with('ratings')
                ->where('id', $orderId)
                ->where('user_id', $user->id)
                ->first();

            if (! $order) {
                return response()->json([
                    'message' => 'Order not found or does not belong to you.'
                ], 404);
            }

            // Ensure order is complete
            if ($order->status !== 'complete') {
                return response()->json([
                    'message' => 'You can only rate after the order is complete.'
                ], 400);
            }

            // Check if already rated
            $existingRating = Rating::where('order_id', $orderId)
                ->where('rated_by', 'user')
                ->first();

            if ($existingRating) {
                return response()->json([
                    'message' => 'You have already rated this order.'
                ], 409);
            }

            // Create the rating
            $rating = Rating::create([
                'order_id'   => $orderId,
                'rated_by'   => 'user',
                'rater_id'   => $user->id,
                'ratee_id'   => $order->freelancer_id,
                'rating'     => $request->rating,
                'review'     => $request->review,
            ]);

            return response()->json([
                'message' => 'Freelancer rated successfully.',
                'data'    => $rating
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to rate freelancer: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred while submitting the rating.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
