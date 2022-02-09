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
        return ceil($value);
    }
    public function getCompanyRatioAttribute($value)
    {
        return ceil($value);
    }
    public function getProgrammerRatioAttribute($value)
    {
        return ceil($value);
    }
    public function getManagmentRatioAttribute($value)
    {
        return ceil($value);
    }
}