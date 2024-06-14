<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Get all products by restaurant
    public function getProductsByRestaurant($userId)
    {
        $user = User::find($userId);

        if (!$user || $user->roles !== 'restaurant') {
            return response()->json([
                'message' => 'Restaurant not found'
            ], 404);
        }

        $products = $user->products;

        return response()->json([
            'message' => 'Products retrieved successfully',
            'products' => $products,
        ], 200);
    }

    // Create a new product
    public function addProduct(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user || $user->roles !== 'restaurant') {
            return response()->json([
                'message' => 'Restaurant not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|integer',
            'stock' => 'required|integer',
            'description' => 'nullable|string',
            'is_available' => 'boolean',
            'is_favorite' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $validated['is_available'] = $validated['is_available'] ?? true;
        $validated['is_favorite'] = $validated['is_favorite'] ?? false;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('images/products', $filename, 'public');
            $validated['image'] = $filePath;
        }

        $product = new Product($validated);
        $product->user_id = $userId;
        $product->save();

        return response()->json([
            'message' => 'Product added successfully',
            'product' => $product,
        ], 201);
    }

    // Update an existing product
    public function updateProduct(Request $request, $productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|integer',
            'stock' => 'sometimes|required|integer',
            'description' => 'sometimes|nullable|string',
            'is_available' => 'sometimes|boolean',
            'is_favorite' => 'sometimes|boolean',
            'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $validated['is_available'] = $validated['is_available'] ?? true;
        $validated['is_favorite'] = $validated['is_favorite'] ?? false;

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }

            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('images/products', $filename, 'public');
            $validated['image'] = $filePath;
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
        ], 200);
    }

    // Delete a product
    public function deleteProduct($productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ], 200);
    }
}
