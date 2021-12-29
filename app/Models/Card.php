<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];
    protected $with = ["join_relations", "join_relations.companies"];
    public function join_relations()
    {
        return $this->hasMany(joinRelations::class, 'card_id');
    }

    public function serial_keys()
    {
        return $this->hasMany(SerialKeyCard::class, 'card_id');
    }
}
