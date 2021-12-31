<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];
    protected $with = ["user", "payment_method", "last_status"];


    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function order_status()
    {
        return $this->hasMany(OrderStatus::class, 'order_id');
    }
    public function last_status()
    {
        return $this->hasOne(OrderStatus::class, "id", "last_order");
    }
}