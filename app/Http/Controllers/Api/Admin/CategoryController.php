<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->query('per_page', 10); // Default: 10 categories per page

            $categories = Category::paginate($perPage);

            if ($categories->isEmpty()) {
                return response()->json([
                    'status'     => 404,
                    'message'    => 'No categories found.',
                    'count'      => 0,
                    'categories' => [],
                ], 404);
            }

            return response()->json([
                'status'     => 200,
                'message'    => 'Categories fetched successfully.',
                'count'      => $categories->total(),
                'categories' => $categories,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Fetch Categories Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching categories.',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }



    public function store(CategoryRequest $request): JsonResponse
    {
        try {
            $filePath = $request->file('path')->store('categories', 'public');

            $category = Category::create([
                'name' => $request->name,
                'path' => $filePath,
                'status' => $request->status ?? 1,
            ]);

            return response()->json([
                'status' => 201,
                'message' => 'Category created successfully.',
                'category' => $category
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Create Category Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error creating category.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $category = Category::find($id);

        if (! $category) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found.'
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Category fetched successfully.',
            'category' => $category
        ]);
    }

    public function update(CategoryRequest $request, $id): JsonResponse
    {
        try {
            $category = Category::find($id);

            if (! $category) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Category not found.'
                ], 404);
            }

            $data = [];

            if ($request->hasFile('path')) {
                if (!empty($category->path) && Storage::disk('public')->exists($category->path)) {
                    Storage::disk('public')->delete($category->path);
                }

                $data['path'] = $request->file('path')->store('categories', 'public');
            } else {
                $data['path'] = $category->path;
            }

            if ($request->filled('name')) {
                $data['name'] = $request->name;
            }

            if ($request->filled('status')) {
                $data['status'] = $request->status;
            }

            $category->update($data);

            return response()->json([
                'status' => 200,
                'message' => 'Category updated successfully.',
                'category' => $category
            ]);
        } catch (\Throwable $e) {
            Log::error('Update Category Error: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error updating category.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

public function destroy($id): JsonResponse
{
    try {
        $category = Category::find($id);

        if (! $category) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found.'
            ], 404);
        }

        // Check if any freelancers are using this category
        $freelancerCount = $category->freelancers()->count(); // Assuming `freelancers()` is a relationship

        if ($freelancerCount > 0) {
            return response()->json([
                'status' => 400,
                'message' => 'Cannot delete category. It is assigned to ' . $freelancerCount . ' freelancer(s).'
            ], 400);
        }

        // Delete image if exists
        if (!empty($category->path) && Storage::disk('public')->exists($category->path)) {
            Storage::disk('public')->delete($category->path);
        }

        // Delete category
        $category->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Category deleted successfully.'
        ]);

    } catch (\Throwable $e) {
        Log::error('Delete Category Error: ' . $e->getMessage());

        return response()->json([
            'status' => 500,
            'message' => 'Error deleting category.',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

}
