<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedDate extends Model
{
    use HasFactory;

    protected $table = 'blocked_dates';
    protected $primaryKey = 'block_id';

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'reason',
    ];

    protected $dates = [
        'date',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user this blocked date belongs to.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
