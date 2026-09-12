<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyImage extends Model
{
    use HasFactory;

    protected $table = 'property_images';

    protected $primaryKey = 'image_id';

    protected $guarded = [];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the property this image belongs to.
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'property_id');
    }
}
