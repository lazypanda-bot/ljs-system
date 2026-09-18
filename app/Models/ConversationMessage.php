<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationMessage extends Model
{
    protected $table = 'conversation_messages';
    protected $primaryKey = 'message_id';

    protected $fillable = ['conversation_id', 'sender_id', 'subject', 'body', 'attachment_path'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }
}