<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Box extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];
    // protected $casts = [""];

    public function getTotalValueAttribute($value)
    {
        return round($value, 2);
    }
    public function getCompanyRatioAttribute($value)
    {
        return round($value, 2);
    }
    public function getProgrammerRatioAttribute($value)
    {
        return round($value, 2);
    }
    public function getManagmentRatioAttribute($value)
    {
        return round($value, 2);
    }
}