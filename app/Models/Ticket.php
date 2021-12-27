<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];
    protected $with = ["user", "images"];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function images()
    {
        return $this->hasMany(Image::class, 'target_id');
    }


    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'ticket_id');
    }
}