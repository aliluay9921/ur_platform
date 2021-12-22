<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class joinRelations extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];

    public function companies()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    public function payment_methods()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
    public function cards()
    {
        return $this->belongsTo(Card::class, 'card_id');
    }
}