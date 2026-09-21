<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyAssign extends Model
{
    use HasFactory;

    protected $table = 'property_assign';
    protected $primaryKey = 'assign_id';

    protected $fillable = [
        'user_id',
        'property_id',
        'assigned_date',
        'status',
        'requested_by',
        'reviewed_by',
        'requested_at',
        'reviewed_at',
    ];

    protected $dates = [
        'assigned_date',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user assigned to this property.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the property being assigned.
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'property_id');
    }

    /**
     * Get the appointments for this assignment.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'assign_id', 'assign_id');
    }
}
