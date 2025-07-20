<?php

namespace App\Http\Controllers\Api\Client\Order;

use App\Events\UserNotificationEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Order\StoreOrderRequest;
use App\Http\Requests\Client\Order\UpdateOrderRequest;
use App\Jobs\SendEmailJob;
use App\Models\Category;
use App\Models\FreelancerProfile;
use App\Models\Order;
use App\Models\Rating;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, $category_id): JsonResponse
    {
        try {
            $category = Category::find($category_id);

            if (! $category) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Category not found.'
                ], 404);
            }

            $user = $request->user();

            $data = array_merge($request->validated(), [
                'user_id'     => $user->id,
                'category_id' => $category->id,
                'status'      => 'in_review'
            ]);

            $order = Order::create($data);

            if ($order->freelancer) {
                $freelancer = $order->freelancer;

                event(new UserNotificationEvent(
                    $freelancer,
                    '📩 New Order Received',
                    "{$user->name} created a new order in {$category->name}"
                ));

                SendEmailJob::dispatch(
                    $freelancer->email,
                    '📩 New Order Received',
                    "{$user->name} created a new order in category: {$category->name}."
                );
            }

            return response()->json([
                'status'  => 201,
                'message' => 'Order created successfully.',
                'order'   => $order
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong while creating the order.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    public function Update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        try {
            $client = $request->user();

            if ($client->id !== $order->user_id) {
                return response()->json([
                    'status'  => 403,
                    'message' => 'You are not authorized to update this order.'
                ], 403);
            }

            if ($order->status !== 'in_review') {
                return response()->json([
                    'status'  => 400,
                    'message' => 'You can only update orders that are in review.'
                ], 400);
            }

            $data = array_filter($request->validated(), fn($v) => $v !== null);
            $order->update($data);

            // Notify the freelancer if assigned
            if ($order->freelancer) {
                $freelancer = $order->freelancer;
                $categoryName = optional($order->category)->name;

                event(new UserNotificationEvent(
                    $freelancer,
                    '✏️ Order Updated',
                    "{$client->name} has updated the order in category: {$categoryName}."
                ));

                SendEmailJob::dispatch(
                    $freelancer->email,
                    '✏️ Order Updated',
                    "{$client->name} has updated the order in category: {$categoryName}."
                );
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Order updated successfully.',
                'order'   => $order
            ], 200);
        } catch (Exception $e) {
            Log::error('Order update failed', [
                'user_id' => optional($request->user())->id,
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong while updating the order.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    public function clientUpdateStatus(Request $request, Order $order): JsonResponse
    {
        try {
            $client = $request->user();

            if ($client->id !== $order->user_id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You are not authorized to update this order.'
                ], 403);
            }

            // Validate new status
            $request->validate([
                'status' => 'required|in:canceled,complete',
            ]);

            if (!in_array($order->status, ['accepted', 'in_review'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'You can only update the status if the order is in_review or accepted.'
                ], 400);
            }

            $order->update(['status' => $request->status]);

            if ($order->freelancer) {
                $freelancer = $order->freelancer;

                $statusText = $request->status === 'complete' ? 'completed' : 'canceled';
                $categoryName = optional($order->category)->name;

                event(new UserNotificationEvent(
                    $freelancer,
                    '📢 Order Status Updated',
                    "{$client->name} has {$statusText} the order in {$categoryName} category."
                ));

                SendEmailJob::dispatch(
                    $freelancer->email,
                    '📢 Order Status Updated',
                    "{$client->name} has {$statusText} the order in category: {$categoryName}."
                );
            }

            return response()->json([
                'status' => 200,
                'message' => 'Order status updated successfully.',
                'order' => $order
            ], 200);
        } catch (Exception $e) {
            Log::error('Client order status update failed', [
                'user_id' => optional($request->user())->id,
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong while updating the order status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getByCategory(int $category_id): JsonResponse
    {
        try {
            $category = Category::find($category_id);

            if (! $category) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Category not found.'
                ], 404);
            }

            $perPage = request()->query('per_page', 10); // Default pagination size

            $freelancers = FreelancerProfile::where('category_id', $category_id)
                ->with('freelancer') // if you want to eager load user info
                ->paginate($perPage);

            return response()->json([
                'status'      => 200,
                'message'     => 'Freelancers fetched successfully.',
                'category'    => $category->name,
                'data'        => $freelancers
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to fetch freelancers by category', [
                'category_id' => $category_id,
                'error'       => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong while fetching freelancers.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getOrders(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status');
            $perPage = $request->query('per_page', 10); // Default to 10 per page

            $query = Order::with('category')
                ->where('user_id', $request->user()->id);

            if ($status && $status !== 'all') {
                if (!in_array($status, ['in_review', 'accepted', 'canceled', 'complete'])) {
                    return response()->json([
                        'status'  => 400,
                        'message' => 'Invalid status value.'
                    ], 400);
                }

                $query->where('status', $status);
            }

            $paginatedOrders = $query->orderByDesc('created_at')->paginate($perPage);

            // Map result to customize output
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
                'data'    => $paginatedOrders
            ], 200);
        } catch (Exception $e) {
            Log::error('Order fetch failed', [
                'user_id' => optional($request->user())->id,
                'error'   => $e->getMessage()
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong while fetching orders.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $order = Order::with([
                'freelancer.profile',
                'category'
            ])->find($id);

            if (! $order) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Order not found.'
                ], 404);
            }

            if (auth()->user()->id !== $order->user_id) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You are not authorized to view this order.'
                ], 403);
            }

            $freelancer = $order->freelancer;
            $freelancerProfile = $freelancer->profile;

            // Load ratings for this order only
            $ratings = Rating::where('order_id', $order->id)
                ->where(function ($query) use ($freelancer) {
                    $query->where('ratee_id', $freelancer->id)
                        ->orWhere('rater_id', $freelancer->id);
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
                    'freelancer'     => [
                        'id'             => $freelancer->id ?? null,
                        'name'           => $freelancerProfile->name ?? null,
                        'average_rating' => $freelancerProfile->average_rating,
                        'ratings'        => $ratings->map(function ($rating) {
                            return [
                                'id'         => $rating->id,
                                'rating'     => $rating->rating,
                                'rated_by'    => $rating->rated_by,
                                'review'    => $rating->comment,
                                'rater_id'   => $rating->rater_id,
                                'ratee_id'   => $rating->ratee_id,
                                'created_at' => $rating->created_at->format('Y-m-d H:i'),
                            ];
                        }),
                    ],
                    'created_at'     => $order->created_at->format('Y-m-d H:i'),
                    'updated_at'     => $order->updated_at->format('Y-m-d H:i'),
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to fetch order details', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong while fetching the order.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function getProfileById($freelancer_id): JsonResponse
    {
        try {
            $profile = FreelancerProfile::where('freelancer_id', $freelancer_id)->first();

            if (! $profile) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Freelancer profile not found.'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Freelancer profile fetched successfully.',
                'profile' => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'city' => $profile->city,
                    'country' => $profile->country,
                    'path' => $profile->path,
                    'freelancer_type' => $profile->freelancer_type,
                    'category_id' => $profile->category_id,
                    'description' => $profile->description,
                    'average_price' => $profile->average_price,
                    'average_rating' => $profile->average_rating,
                ]
            ], 200);
        } catch (Exception $e) {
            Log::error('Failed to fetch freelancer profile', [
                'freelancer_id' => $freelancer_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong while fetching freelancer profile.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
