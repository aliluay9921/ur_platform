<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];


    public function order_key_type()
    {
        return $this->belongsTo(OrderKeyType::class, 'order_key_type_id');
    }
    public function join_relations()
    {
        return $this->hasMany(joinRelations::class, 'payment_method_id');
    }
}