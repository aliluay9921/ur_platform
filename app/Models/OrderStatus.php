<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatus extends Model
{
    use HasFactory, Uuids;

    protected $guarded = [];
    protected $with = ["status", "relations"];
    // public function deposit()
    // {
    //     return $this->belongsTo(Deposit::class, 'order_id');
    // }
    // public function withdraw()
    // {
    //     return $this->belongsTo(Withdraw::class, 'order_id');
    // }

    public function relations()
    {
        if ($this->type == 0) {
            return $this->belongsTo(Deposit::class, 'order_id');
        } elseif ($this->type == 1) {
            return $this->belongsTo(Withdraw::class, 'order_id');
        }
    }
    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}