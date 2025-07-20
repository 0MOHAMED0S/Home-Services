<?php

namespace App\Http\Controllers\Api\Freelancer\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Events\FreelancerNotificationEvent;
use App\Jobs\SendEmailJob;
use App\Models\Rating;

class OrderController extends Controller
{
    public function freelancerUpdateStatus(Request $request, Order $order): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:accepted,canceled',
            ]);

            $freelancer = $request->user();

            if ($freelancer->id !== $order->freelancer_id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You are not authorized to update this order.'
                ], 403);
            }

            if (in_array($order->status, ['canceled', 'complete'])) {
                return response()->json([
                    'status' => 400,
                    'message' => "This order is already {$order->status} and cannot be changed."
                ], 400);
            }

            if ($order->status !== 'in_review') {
                return response()->json([
                    'status' => 400,
                    'message' => 'You can only update orders that are still in review.'
                ], 400);
            }

            $order->update(['status' => $request->status]);

            $user = $order->user;

            $title = '📦 Order Update';
            $message = "Your order #{$order->id} was {$request->status} by {$freelancer->name}.";

            event(new FreelancerNotificationEvent($user, $title, $message));

            SendEmailJob::dispatch($user->email, $title, $message);

            return response()->json([
                'status' => 200,
                'message' => 'Order status updated successfully.',
                'order' => $order
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Order status update failed by freelancer', [
                'freelancer_id' => optional($request->user())->id,
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Failed to update order status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getFreelancerOrders(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status');
            $perPage = $request->query('per_page', 10);

            $freelancerId = $request->user()->id;

            $query = Order::with('category')
                ->where('freelancer_id', $freelancerId);

            if ($status && $status !== 'all') {
                if (!in_array($status, ['in_review', 'accepted', 'canceled', 'complete'])) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Invalid status value.',
                    ], 400);
                }

                $query->where('status', $status);
            }

            $paginatedOrders = $query->orderByDesc('created_at')->paginate($perPage);

            // Transform data
            $paginatedOrders->getCollection()->transform(function ($order) {
                return [
                    'quoted_price' => $order->quoted_price,
                    'start_date'   => $order->start_date,
                    'status'       => $order->status,
                    'category'     => $order->category->name ?? null,
                ];
            });

            return response()->json([
                'status'  => 200,
                'message' => 'Orders fetched successfully.',
                'data'    => $paginatedOrders,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Freelancer order fetch failed', [
                'freelancer_id' => optional($request->user())->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong while fetching orders.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function showFreelancerOrder($id): JsonResponse
    {
        try {
            $order = Order::with(['category', 'freelancer.profile', 'user'])->find($id);

            if (! $order) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Order not found.'
                ], 404);
            }

            if ($order->freelancer_id !== Auth::id()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You are not authorized to view this order.'
                ], 403);
            }

            // Get ratings related to this order (either direction)
            $ratings = Rating::where('order_id', $order->id)
                ->where(function ($query) use ($order) {
                    $query->where('ratee_id', $order->freelancer_id)
                        ->orWhere('ratee_id', $order->user_id);
                })
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Order fetched successfully.',
                'order' => [
                    'id'             => $order->id,
                    'quoted_price'   => $order->quoted_price,
                    'billing_unit'   => $order->billing_unit,
                    'status'         => $order->status,
                    'payment_method' => $order->payment_method,
                    'start_date'     => $order->start_date,
                    'description'    => $order->description,
                    'city'           => $order->city,
                    'country'        => $order->country,
                    'category_name'  => $order->category->name ?? null,
                    'client'         => [
                        'id'   => $order->user->id ?? null,
                        'name' => $order->user->name ?? null,
                    ],
                    'ratings' => $ratings->map(function ($rating) {
                        return [
                            'id'         => $rating->id,
                            'rating'     => $rating->rating,
                            'rated_by'     => $rating->rated_by,
                            'review'    => $rating->comment,
                            'rater_id'   => $rating->rater_id,
                            'ratee_id'   => $rating->ratee_id,
                            'created_at' => $rating->created_at->format('Y-m-d H:i'),
                        ];
                    }),
                    'created_at'     => $order->created_at->format('Y-m-d H:i'),
                    'updated_at'     => $order->updated_at->format('Y-m-d H:i'),
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to fetch freelancer order details', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong while fetching the order.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getClientProfileByUserId($user_id): JsonResponse
    {
        try {
            $user = User::with('profile')->find($user_id);

            if (! $user || ! $user->profile) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Client profile not found.'
                ], 404);
            }

            $profile = $user->profile;

            return response()->json([
                'status' => 200,
                'message' => 'Client profile fetched successfully.',
                'profile' => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'city' => $profile->city,
                    'country' => $profile->country,
                    'path' => $profile->path,
                    'average_rating' => $profile->average_rating,
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to fetch client profile', [
                'user_id' => $user_id,
                'error'   => $e->getMessage()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong while fetching client profile.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
