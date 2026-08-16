<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyDetail extends Model
{
    protected $table = 'property_details';

    protected $primaryKey = 'details_id';

    protected $guarded = [];
}
