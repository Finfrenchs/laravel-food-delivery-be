@extends('layouts.app')

@section('title', 'Order Detail')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Order Detail</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('orders.index') }}">Orders</a></div>
                    <div class="breadcrumb-item">Order Detail</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Order ID: {{ $order->id }}</h4>
                            </div>
                            <div class="card-body">
                                <h5>User Information</h5>
                                <p><strong>Name:</strong> {{ $order->user->name }}</p>
                                <p><strong>Email:</strong> {{ $order->user->email }}</p>
                                <p><strong>Phone:</strong> {{ $order->user->phone }}</p>
                                <p><strong>Address:</strong> {{ $order->user->address }}</p>

                                <h5>Restaurant Information</h5>
                                <p><strong>Name:</strong> {{ $order->restaurant->restaurant_name }}</p>
                                <p><strong>Address:</strong> {{ $order->restaurant->restaurant_address }}</p>

                                <h5>Driver Information</h5>
                                @if($order->driver)
                                    <p><strong>Name:</strong> {{ $order->driver->name }}</p>
                                    <p><strong>Phone:</strong> {{ $order->driver->phone }}</p>
                                @else
                                    <p>No driver assigned yet.</p>
                                @endif

                                <h5>Order Details</h5>
                                <p><strong>Total Price:</strong> {{ $order->total_price }}</p>
                                <p><strong>Shipping Cost:</strong> {{ $order->shipping_cost }}</p>
                                <p><strong>Total Bill:</strong> {{ $order->total_bill }}</p>
                                <p><strong>Status:</strong> {{ $order->status }}</p>
                                <p><strong>Created At:</strong> {{ $order->created_at }}</p>

                                <h5>Order Items</h5>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->orderItems as $item)
                                            <tr>
                                                <td>{{ $item->product->name }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ $item->price }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer text-right">
                                <a href="{{ route('orders.index') }}" class="btn btn-primary">Back to Orders</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraries -->
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>
@endpush
