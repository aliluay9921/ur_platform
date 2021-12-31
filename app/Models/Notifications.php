<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];

    /**
     * Get the user that owns the Notifications
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function to_user()
    {
        return $this->belongsTo(User::class, 'to_user');
    }
    public function from_user()
    {
        return $this->belongsTo(User::class, 'from_user');
    }

    public function transactions()
    {
        return $this->belongsTo(Transaction::class, 'target_id');
    }
    public function order_status()
    {
        return $this->belongsTo(OrderStatus::class, 'target_id');
    }
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'target_id');
    }
    public function comment()
    {
        return $this->belongsTo(TicketComment::class, 'target_id');
    }
}