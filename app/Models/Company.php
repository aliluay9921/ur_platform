<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];
    protected $with = ["images"];


    public function images()
    {
        return $this->hasMany(Image::class, 'target_id');
    }
    public function join_relations()
    {
        return $this->hasMany(joinRelations::class, 'company_id');
    }
}