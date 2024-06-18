<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // User: Create a new order
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:users,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'total_price' => 'required|integer|min:0',
            'shipping_cost' => 'required|integer|min:0',
            'total_bill' => 'required|integer|min:0',
        ]);

        $order = DB::transaction(function () use ($validated) {
            $order = new Order([
                'user_id' => auth()->id(),
                'restaurant_id' => $validated['restaurant_id'],
                'total_price' => $validated['total_price'],
                'shipping_cost' => $validated['shipping_cost'],
                'total_bill' => $validated['total_bill'],
                'status' => 'pending',
            ]);
            $order->save();

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);

                $orderItem = new OrderItem([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);
                $orderItem->save();
            }

            return $order;
        });

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ], 201);
    }



    // User: Purchase order
    public function purchaseOrder(Request $request, $orderId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,e_wallet',
            'payment_e_wallet' => 'nullable|required_if:payment_method,e_wallet|string',
        ]);

        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $order->status = 'purchase';
        $order->payment_method = $validated['payment_method'];
        if ($validated['payment_method'] === 'e_wallet') {
            $order->payment_e_wallet = $validated['payment_e_wallet'];
        }
        $order->save();

        return response()->json([
            'message' => 'Order purchased successfully',
            'order' => $order,
        ], 200);
    }

    // User: Order history list
    public function orderHistory()
    {
        $orders = Order::where('user_id', auth()->id())->get();

        return response()->json([
            'message' => 'Order history retrieved successfully',
            'orders' => $orders,
        ], 200);
    }

    // User: Order history detail
    public function orderDetail($orderId)
    {
        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->with('orderItems.product')->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Order detail retrieved successfully',
            'order' => $order,
        ], 200);
    }

    // User: Cancel order
    public function cancelOrder($orderId)
    {
        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        if (!in_array($order->status, ['pending', 'purchase'])) {
            return response()->json([
                'message' => 'Cannot cancel order'
            ], 400);
        }

        $order->status = 'cancel';
        $order->save();

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $order,
        ], 200);
    }

    // Restaurant: Get orders by status
    public function getOrdersByRestaurant()
    {
        $restaurantId = auth()->id();
        $orders = Order::where('restaurant_id', $restaurantId)->orderBy('status')->get();

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'orders' => $orders,
        ], 200);
    }

    // Restaurant: Prepare order
    public function prepareOrder($orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status !== 'purchase') {
            return response()->json([
                'message' => 'Cannot prepare order'
            ], 400);
        }

        $order->status = 'preparing';
        $order->save();

        return response()->json([
            'message' => 'Order is being prepared',
            'order' => $order,
        ], 200);
    }

    // Restaurant: Mark order as ready for pickup
    public function markOrderAsReady($orderId)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status !== 'preparing') {
            return response()->json([
                'message' => 'Cannot mark order as ready'
            ], 400);
        }

        // Cari pengemudi yang sedang tidak dalam status waiting pickup atau on delivery secara acak
        $driver = DB::table('users')
            ->where('roles', 'driver')
            ->whereNotIn('id', function($query) {
                $query->select('driver_id')
                    ->from('orders')
                    ->whereIn('status', ['waiting pickup', 'on delivery']);
            })
            ->inRandomOrder()
            ->first();

        if (!$driver) {
            return response()->json([
                'message' => 'No available drivers found'
            ], 400);
        }

        $order->status = 'waiting pickup';
        $order->driver_id = $driver->id;
        $order->save();

        return response()->json([
            'message' => 'Order is ready for pickup and assigned to a driver',
            'order' => $order,
        ], 200);
    }

    // Restaurant: Get report
    public function getRestaurantReport()
    {
        $restaurantId = auth()->id();

        $ordersToday = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $menusAvailable = Product::where('user_id', $restaurantId)
            ->where('is_available', true)
            ->count();

        $totalTransactionsToday = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', now()->toDateString())
            ->sum('total_price');

        return response()->json([
            'message' => 'Report retrieved successfully',
            'orders_today' => $ordersToday,
            'menus_available' => $menusAvailable,
            'total_transactions_today' => $totalTransactionsToday,
        ], 200);
    }

    // Driver: Get orders waiting for pickup
    public function getOrdersWaitingPickup()
    {
        $orders = Order::where('status', 'waiting pickup')->get();

        return response()->json([
            'message' => 'Orders retrieved successfully',
            'orders' => $orders,
        ], 200);
    }

    // Driver: Take delivery
    public function takeDelivery($orderId)
    {
        $order = Order::where('id', $orderId)->first();

        if (!$order || $order->status !== 'waiting pickup') {
            return response()->json([
                'message' => 'Order not found or not available for pickup'
            ], 404);
        }

        $order->driver_id = auth()->id();
        $order->status = 'on delivery';
        $order->save();

        return response()->json([
            'message' => 'Order picked up for delivery',
            'order' => $order,
        ], 200);
    }

    // Driver: Mark order as done
    public function markOrderAsDone($orderId)
    {
        $order = Order::where('id', $orderId)->where('driver_id', auth()->id())->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $order->status = 'done';
        $order->save();

        return response()->json([
            'message' => 'Order marked as done successfully',
            'order' => $order,
        ], 200);
    }

    // Driver: Get order detail by ID
    public function getOrderDetailById($orderId)
    {
        $order = Order::where('id', $orderId)->where('driver_id', auth()->id())->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Order detail retrieved successfully',
            'order' => $order,
        ], 200);
    }

    // Driver: Get all orders assigned to the driver
    public function getDriverOrders()
    {
        $driverId = auth()->id();
        $orders = Order::where('driver_id', $driverId)
            ->whereIn('status', ['waiting pickup', 'done', 'cancel'])
            ->get();

        return response()->json([
            'message' => 'Driver orders retrieved successfully',
            'orders' => $orders,
        ], 200);
    }

}
