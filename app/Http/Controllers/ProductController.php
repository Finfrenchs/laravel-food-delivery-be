<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Get products with pagination, filters, and sorting
        $products = DB::table('products')
            ->join('users', 'products.user_id', '=', 'users.id')
            ->select('products.*', 'users.name as restaurant_name')
            ->when($request->input('name'), function ($query, $name) {
                return $query->where('products.name', 'like', '%' . $name . '%');
            })
            ->when($request->input('restaurant_id'), function ($query, $restaurantId) {
                return $query->where('products.user_id', $restaurantId);
            })
            ->when($request->input('sort_by'), function ($query, $sortBy) use ($request) {
                $order = $request->input('order', 'asc');
                return $query->orderBy($sortBy, $order);
            })
            ->orderBy('products.created_at', 'desc') // Default sorting by created_at in descending order
            ->paginate(10);

        return view('pages.product.index', compact('products'));
    }
}
