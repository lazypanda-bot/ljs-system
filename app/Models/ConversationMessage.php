<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ConversationMessage extends Model
{
    protected $table = 'conversation_messages';
    protected $primaryKey = 'message_id';

    protected $fillable = ['conversation_id', 'sender_id', 'subject', 'body', 'attachment_path'];

    protected static function booted(): void
    {
        static::deleting(function (self $message): void {
            if ($message->attachment_path && str_starts_with($message->attachment_path, 'message-attachments/')) {
                Storage::disk('local')->delete($message->attachment_path);
                Storage::disk('public')->delete($message->attachment_path);
            }
        });
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'user_id');
    }
}