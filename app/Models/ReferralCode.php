<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCode extends Model
{
    protected $table = 'referral_codes';
    protected $primaryKey = 'referral_code_id';

    protected $fillable = [
        'user_id',
        'property_id',
        'code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id', 'property_id');
    }
}
