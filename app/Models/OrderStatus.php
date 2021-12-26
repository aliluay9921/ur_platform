<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use function PHPSTORM_META\type;

class OrderStatus extends Model
{
    use HasFactory, Uuids;

    protected $guarded = [];
    protected $with = ["status", "transactions"];


    public function deposit()
    {
        return $this->belongsTo(Deposit::class, 'order_id');
    }
    public function withdraw()
    {
        return $this->belongsTo(Withdraw::class, 'order_id');
    }

    public function transactions()
    {
        return $this->belongsTo(Transaction::class, 'order_id');
    }



    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}