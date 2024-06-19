<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['user', 'restaurant', 'driver'])->paginate(10);
        return view('pages.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['user', 'restaurant', 'driver', 'orderItems.product'])->findOrFail($id);
        return view('pages.orders.show', compact('order'));
    }
}
