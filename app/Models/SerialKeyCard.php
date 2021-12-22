<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SerialKeyCard extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function card()
    {
        return $this->belongsTo(Card::class, 'card_id');
    }
}