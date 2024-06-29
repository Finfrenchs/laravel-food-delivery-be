<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Xendit\Configuration;

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

        $user = auth()->user();
        $shipping_address = $user->address;
        $shipping_latlong = $user->latlong;

        $order = DB::transaction(function () use ($validated, $shipping_address, $shipping_latlong) {
            $order = new Order([
                'user_id' => auth()->id(),
                'restaurant_id' => $validated['restaurant_id'],
                'total_price' => $validated['total_price'],
                'shipping_cost' => $validated['shipping_cost'],
                'total_bill' => $validated['total_bill'],
                'shipping_address' => $shipping_address,
                'shipping_latlong' => $shipping_latlong,
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

        $this->sendNotificationToRestaurant($order->restaurant_id, 'New Order', 'You have received a new order.');

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order,
        ], 201);
    }



    // User: Purchase order
    public function __construct()
    {
        Configuration::setXenditKey('xnd_production_Df7zy1YOav5w5bcJXzJVHpNnbXU9x4r7FfCZfJN2p1PVOGriq4Qm968k723rOxtw');
    }

    ///One-Time Payment via Redirect URL
    public function purchaseOrder(Request $request, $orderId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,e_wallet',
            'payment_e_wallet' => 'nullable|required_if:payment_method,e_wallet|string',
            'mobile_number' => 'nullable|required_if:payment_e_wallet,ID_OVO|string'
        ]);

        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($validated['payment_method'] === 'e_wallet') {
            $apiInstance = new \Xendit\PaymentRequest\PaymentRequestApi();
            $idempotency_key = uniqid();
            ///$for_user_id = auth()->id();

            $channel_properties = [
                'success_return_url' => 'http://10.1.10.179:8000'
            ];

            // Menambahkan mobile_number jika e-wallet adalah OVO
            if ($validated['payment_e_wallet'] === 'ID_OVO') {
                $channel_properties['mobile_number'] = $validated['mobile_number'];
            }

            $payment_request_parameters = new \Xendit\PaymentRequest\PaymentRequestParameters([
                'reference_id' => 'order-' . $orderId,
                'amount' => $order->total_bill,
                'currency' => 'IDR',
                'country' => 'ID',
                'payment_method' => [
                    'type' => 'EWALLET',
                    'ewallet' => [
                        'channel_code' => $validated['payment_e_wallet'],
                        'channel_properties' => $channel_properties
                    ],
                    'reusability' => 'ONE_TIME_USE'
                ]
            ]);

            try {
                $result = $apiInstance->createPaymentRequest($idempotency_key, null, $payment_request_parameters);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_type' => $validated['payment_method'],
                    'payment_provider' => $validated['payment_e_wallet'],
                    'amount' => $order->total_bill,
                    'status' => 'pending',
                    'xendit_id' => $result['id'],
                ]);

                return response()->json(['message' => 'Payment created successfully', 'order' => $order, 'payment' => $result], 200);

            } catch (\Xendit\XenditSdkException $e) {
                return response()->json(['message' => 'Failed to create payment', 'error' => $e->getMessage(), 'full_error' => $e->getFullError()], 500);
            }
        } else {
            $order->status = 'purchase';
            $order->payment_method = $validated['payment_method'];
            $order->save();

            $this->sendNotificationToRestaurant($order->restaurant_id, 'Order Purchased', 'An order has been purchased and is ready to be prepared.');

            return response()->json(['message' => 'Order purchased successfully', 'order' => $order], 200);
        }
    }


    /// Subsequent Tokenized E-Wallet Payments
    public function purchaseOrderWithToken(Request $request, $orderId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|in:bank_transfer,e_wallet',
            'payment_e_wallet' => 'nullable|required_if:payment_method,e_wallet|string',
            'payment_method_id' => 'nullable|required_if:payment_method,e_wallet|string',
        ]);

        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if ($validated['payment_method'] === 'e_wallet') {
            $apiInstance = new \Xendit\PaymentRequest\PaymentRequestApi();
            $idempotency_key = uniqid();
            $for_user_id = auth()->id();

            $payment_request_parameters = new \Xendit\PaymentRequest\PaymentRequestParameters([
                'reference_id' => 'order-' . $orderId,
                'amount' => $order->total_bill,
                'currency' => 'IDR',
                'payment_method_id' => $validated['payment_method_id'],
                'metadata' => [
                    'order_id' => $orderId,
                    'user_id' => $order->user->id,
                ]
            ]);

            try {
                $result = $apiInstance->createPaymentRequest($idempotency_key, $for_user_id, $payment_request_parameters);

                Payment::create([
                    'order_id' => $order->id,
                    'payment_type' => $validated['payment_method'],
                    'payment_provider' => $validated['payment_e_wallet'],
                    'amount' => $order->total_bill,
                    'status' => 'pending',
                    'xendit_id' => $result['id'],
                ]);

                return response()->json(['message' => 'Payment created successfully', 'order' => $order, 'payment' => $result], 200);

            } catch (\Xendit\XenditSdkException $e) {
                return response()->json(['message' => 'Failed to create payment', 'error' => $e->getMessage(), 'full_error' => $e->getFullError()], 500);
            }
        } else {
            $order->status = 'purchase';
            $order->payment_method = $validated['payment_method'];
            $order->save();

            $this->sendNotificationToRestaurant($order->restaurant_id, 'Order Purchased', 'An order has been purchased and is ready to be prepared.');

            return response()->json(['message' => 'Order purchased successfully', 'order' => $order], 200);
        }
    }

    // Get Payment Method
    public function getPaymentMethods()
    {
        $paymentMethods = [
            'e_wallet' => [
                'ID_OVO' => 'OVO',
                'ID_DANA' => 'DANA',
                'ID_LINKAJA' => 'LinkAja',
                'ID_SHOPEEPAY' => 'ShopeePay',
            ]
        ];

        return response()->json([
            'message' => 'Payment methods retrieved successfully',
            'payment_methods' => $paymentMethods
        ], 200);
    }



    // public function purchaseOrder(Request $request, $orderId)
    // {
    //     $validated = $request->validate([
    //         'payment_method' => 'required|in:bank_transfer,e_wallet',
    //         'payment_e_wallet' => 'nullable|required_if:payment_method,e_wallet|string',
    //         'mobile_number' => 'nullable|required_if:payment_method,e_wallet|string',
    //     ]);

    //     $order = Order::where('id', $orderId)->where('user_id', auth()->id())->first();

    //     if (!$order) {
    //         return response()->json([
    //             'message' => 'Order not found'
    //         ], 404);
    //     }

    //     if ($validated['payment_method'] === 'e_wallet') {
    //         $paymentParams = [
    //             'reference_id' => 'order-' . $orderId,
    //             'currency' => 'IDR',
    //             'amount' => $order->total_bill,
    //             'checkout_method' => 'ONE_TIME_PAYMENT',
    //             'channel_code' => $validated['payment_e_wallet'],
    //             'channel_properties' => [
    //                 'mobile_number' => $validated['mobile_number'],
    //             ],
    //             'metadata' => [
    //                 'order_id' => $orderId,
    //                 'user_id' => $order->user->id,
    //             ],
    //         ];

    //         try {
    //             $payment = Xendit\PaymentRequest\EWallet::createEWalletCharge($paymentParams);

    //             Payment::create([
    //                 'order_id' => $order->id,
    //                 'payment_type' => $validated['payment_method'],
    //                 'payment_provider' => $validated['payment_e_wallet'],
    //                 'amount' => $order->total_bill,
    //                 'status' => 'pending',
    //                 'xendit_id' => $payment['id'],
    //             ]);

    //             // Jika pembayaran berhasil, perbarui status order menjadi 'purchase'
    //             $order->status = 'purchase';
    //             $order->payment_method = $validated['payment_method'];
    //             $order->payment_e_wallet = $validated['payment_e_wallet'];
    //             $order->save();

    //             $this->sendNotificationToRestaurant($order->restaurant_id, 'Order Purchased', 'An order has been purchased and is ready to be prepared.');

    //             return response()->json([
    //                 'message' => 'Order purchased and payment created successfully',
    //                 'order' => $order,
    //                 'payment' => $payment,
    //             ], 200);

    //         } catch (\Exception $e) {
    //             // Jika pembayaran gagal, ubah status order menjadi 'cancel'
    //             $order->status = 'cancel';
    //             $order->save();

    //             return response()->json([
    //                 'message' => 'Failed to create payment',
    //                 'error' => $e->getMessage(),
    //             ], 500);
    //         }
    //     } else {
    //         // Jika metode pembayaran bukan e-wallet, langsung perbarui status order
    //         $order->status = 'purchase';
    //         $order->payment_method = $validated['payment_method'];
    //         $order->save();

    //         $this->sendNotificationToRestaurant($order->restaurant_id, 'Order Purchased', 'An order has been purchased and is ready to be prepared.');

    //         return response()->json([
    //             'message' => 'Order purchased successfully',
    //             'order' => $order,
    //         ], 200);
    //     }
    // }


    // User: Order history list
    public function orderHistory()
    {
        $orders = Order::where('user_id', auth()->id())->with('orderItems.product',  'user')->get();

        return response()->json([
            'message' => 'Order history retrieved successfully',
            'orders' => $orders,
        ], 200);
    }

    // User: Order history detail
    public function orderDetail($orderId)
    {
        $order = Order::where('id', $orderId)->where('user_id', auth()->id())->with('orderItems.product', 'user')->first();

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
        $orders = Order::where('restaurant_id', $restaurantId)
        ->with('orderItems.product', 'user')
        ->orderBy('status')
        ->get();

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

        $this->sendNotificationToUser($order->user_id, 'Order Preparing', 'Your order is being prepared.');

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

        if ($order->driver_id) {
            return response()->json([
                'message' => 'Driver already assigned to this order'
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

        $this->sendNotificationToUser($order->user_id, 'Order Ready for Pickup', 'Your order is ready for pickup.');
        $this->sendNotificationToDriver($order->driver_id, 'Order Assigned', 'You have been assigned a new order for pickup.');

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

        // Total orders
        $totalOrders = Order::where('restaurant_id', $restaurantId)->count();

        // Total transactions (all time)
        $totalTransactions = Order::where('restaurant_id', $restaurantId)->sum('total_price');

        return response()->json([
            'message' => 'Report retrieved successfully',
            'orders_today' => $ordersToday,
            'menus_available' => $menusAvailable,
            'total_transactions_today' => $totalTransactionsToday,
            'total_orders' => $totalOrders,
            'total_transactions' => $totalTransactions,
        ], 200);
    }

    // Driver: Get orders waiting for pickup
    public function getOrdersWaitingPickup()
    {
        $orders = Order::where('status', 'waiting pickup')->with('orderItems.product',  'user')->get();

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

        if ($order->driver_id && $order->driver_id !== auth()->id()) {
            return response()->json([
                'message' => 'This order is already assigned to another driver'
            ], 403);
        }

        $order->driver_id = auth()->id();
        $order->status = 'on delivery';
        $order->save();

        $this->sendNotificationToUser($order->user_id, 'Order on Delivery', 'Your order is on the way.');

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

        $this->sendNotificationToUser($order->user_id, 'Order Delivered', 'Your order has been delivered successfully.');
        $this->sendNotificationToRestaurant($order->restaurant_id, 'Order Completed', 'The order has been completed successfully.');


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

        Log::info('Fetching orders for driver', ['driverId' => $driverId]);

        $orders = Order::where('driver_id', $driverId)
            ->whereIn('status', ['waiting pickup', 'done', 'cancel'])
            ->get();

        Log::info('Orders retrieved', ['orders' => $orders]);

        return response()->json([
            'message' => 'Driver orders retrieved successfully',
            'orders' => $orders,
        ], 200);
    }

    //Send Notification Function
    // Method for send notification to  user
    public function sendNotificationToUser($userId, $title, $message)
    {
        $user = User::find($userId);
        if ($user && $user->fcm_id) {
            $token = $user->fcm_id;

            // Kirim notifikasi ke perangkat Android
            $messaging = app('firebase.messaging');
            $notification = Notification::create($title, $message);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);

            try {
                $messaging->send($message);
            } catch (\Exception $e) {
                Log::error('Failed to send notification', ['error' => $e->getMessage()]);
            }
        }
    }

    // Method for send notification to restaurant
    public function sendNotificationToRestaurant($restaurantId, $title, $message)
    {
        $restaurant = User::find($restaurantId);
        if ($restaurant && $restaurant->fcm_id) {
            $token = $restaurant->fcm_id;

            // Kirim notifikasi ke perangkat Android
            $messaging = app('firebase.messaging');
            $notification = Notification::create($title, $message);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);

            try {
                $messaging->send($message);
            } catch (\Exception $e) {
                Log::error('Failed to send notification', ['error' => $e->getMessage()]);
            }
        }
    }

    // Method send notification to driver
    public function sendNotificationToDriver($driverId, $title, $message)
    {
        $driver = User::find($driverId);
        if ($driver && $driver->fcm_id) {
            $token = $driver->fcm_id;

            // Kirim notifikasi ke perangkat Android
            $messaging = app('firebase.messaging');
            $notification = Notification::create($title, $message);

            $message = CloudMessage::withTarget('token', $token)
                ->withNotification($notification);

            try {
                $messaging->send($message);
            } catch (\Exception $e) {
                Log::error('Failed to send notification', ['error' => $e->getMessage()]);
            }
        }
    }
}
