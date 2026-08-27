<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    // Explicitly tell Laravel to use your actual database table name
    protected $table = 'property_listings';

    // If your primary key column is not 'id', define it here too:
    protected $primaryKey = 'property_id';

    protected $guarded = [];

    // Relationships
    public function images()
    {
        return $this->hasMany(PropertyImage::class, 'property_id', 'property_id');
    }

    public function details()
    {
        return $this->hasOne(PropertyDetail::class, 'property_id', 'property_id');
    }

    public function user()
{
    return $this->belongsTo(User::class, 'user_id', 'user_id');
}
}