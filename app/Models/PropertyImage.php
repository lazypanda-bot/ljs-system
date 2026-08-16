<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyImage extends Model
{
    protected $table = 'property_images';

    protected $primaryKey = 'image_id';

    protected $guarded = [];
}
