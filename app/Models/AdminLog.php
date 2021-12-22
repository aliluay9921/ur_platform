<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];


    public function deposit()
    {
        return $this->belongsTo(Deposit::class, 'target_id')->where("type", 0);
    }
    public function withdraw()
    {
        return $this->belongsTo(Withdraw::class, 'target_id')->where("type", 1);
    }
    public function cards()
    {
        return $this->belongsTo(Card::class, 'target_id')->where("type", 2);
    }
}