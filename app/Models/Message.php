<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';
    protected $primaryKey = 'message_id';

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'subject',
        'message',
        'message_type',
        'attachment',
        'sender_type',
        'receiver_type',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    /**
     * Get the user who sent this message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }

    /**
     * Get the user who received this message.
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id', 'user_id');
    }
}
