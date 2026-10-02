<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyInquiry extends Model
{
    protected $primaryKey = 'inquiry_id';

    protected $fillable = ['property_id', 'conversation_id', 'client_id', 'recipient_id'];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'property_id');
    }
}