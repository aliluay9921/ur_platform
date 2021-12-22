<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    use HasFactory, Uuids;

    protected $guarded = [];
    protected $with = ["status"];
    public function deposit()
    {
        return $this->belongsTo(Deposit::class, 'order_id')->where("type", 0);
    }
    public function withdraw()
    {
        return $this->belongsTo(Withdraw::class, 'order_id')->where("type", 1);
    }
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}