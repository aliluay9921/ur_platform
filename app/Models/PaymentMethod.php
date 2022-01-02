<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory, Uuids, SoftDeletes;
    protected $guarded = [];
    protected $with = ["images", "order_key_type", "join_relations", "join_relations.companies"];
    protected $dates = ["delete_at"];



    public function order_key_type()
    {
        return $this->belongsTo(OrderKeyType::class, 'order_key_type_id');
    }
    public function join_relations()
    {
        return $this->hasMany(joinRelations::class, 'payment_method_id');
    }


    public function images()
    {
        return $this->hasMany(Image::class, 'target_id');
    }
}