<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $table = 'property_listings';
    protected $primaryKey = 'property_id';
    protected $guarded = [];

    public function images()
    {
        return $this->hasMany(PropertyImage::class, 'property_id', 'property_id');
    }

    public function details()
    {
        return $this->hasOne(PropertyDetail::class, 'property_id', 'property_id');
    }
}