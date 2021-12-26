<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminLog extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];
    protected $with = ["transactions", "cards"];

    public function transactions()
    {
        return $this->belongsTo(Transaction::class, 'target_id');
    }

    public function cards()
    {
        return $this->belongsTo(Card::class, 'target_id');
    }
}