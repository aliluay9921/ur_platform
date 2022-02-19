<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Card extends Model
{
    use HasFactory, Uuids, SoftDeletes;
    protected $guarded = [];
    protected $with = ["join_relations", "join_relations.companies"];
    protected $appends = ["Points"];
    protected $dates = ["delete_at"];

    public function join_relations()
    {
        return $this->hasMany(joinRelations::class, 'card_id');
    }

    public function serial_keys()
    {
        return $this->hasMany(SerialKeyCard::class, 'card_id');
    }

    public function getPointsAttribute()
    {
        $relations = joinRelations::where("card_id", $this->id)->first();
        if ($relations) {
            $cuurency = $relations->companies->currency_type;
            $change_currency = ChangeCurrncy::where("currency", $cuurency)->first();
            return  $change_currency->points * $this->card_buy;
        } else {
            return 0;
        }
    }
}