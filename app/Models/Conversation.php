<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $primaryKey = 'conversation_id';

    protected $fillable = ['type', 'team_id', 'created_by'];

    protected static function booted(): void
    {
        static::deleting(function (self $conversation): void {
            $conversation->messages()->get()->each->delete();
        });
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id', 'conversation_id');
    }

    public function messages()
    {
        return $this->hasMany(ConversationMessage::class, 'conversation_id', 'conversation_id');
    }

    public function propertyInquiries()
    {
        return $this->hasMany(PropertyInquiry::class, 'conversation_id', 'conversation_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id', 'team_id');
    }
}