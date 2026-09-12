<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'property_details';

    protected $primaryKey = 'details_id';

    protected $guarded = [];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the property this detail belongs to.
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'property_id');
    }

    /**
     * Get the amenities for this property detail.
     */
    public function amenities()
    {
        return $this->hasMany(PropertyAmenity::class, 'details_id', 'details_id');
    }
}
