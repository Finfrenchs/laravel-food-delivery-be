<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'driver_id',
        'total_price',
        'shipping_cost',
        'total_bill',
        'payment_method',
        'payment_e_wallet',
        'shipping_address',
        'shipping_latlong',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(User::class, 'restaurant_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
