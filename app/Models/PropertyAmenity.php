<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyAmenity extends Model
{
    use HasFactory;

    protected $table = 'property_amenities';
    protected $primaryKey = 'amenity_id';

    protected $fillable = [
        'details_id',
        'amenity_name',
    ];

    public $timestamps = false;

    /**
     * Get the property detail this amenity belongs to.
     */
    public function propertyDetail()
    {
        return $this->belongsTo(PropertyDetail::class, 'details_id', 'details_id');
    }
}
