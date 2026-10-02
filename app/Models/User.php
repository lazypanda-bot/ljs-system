<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'contact_number',
        'profile_photo',
        'account_status',
        'role_name',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the agent profile for this user.
     */
    public function agent()
    {
        return $this->hasOne(Agent::class, 'user_id', 'user_id');
    }

    /**
     * Get the professional profile for this user.
     */
    public function professionalProfile()
    {
        return $this->hasOne(Agent::class, 'user_id', 'user_id');
    }

    /**
     * Get the teams created by this user.
     */
    public function teams()
    {
        return $this->hasMany(Team::class, 'user_id', 'user_id');
    }

    /**
     * Snake-case role key derived from role_name, e.g. "Lead Broker" -> "lead_broker".
     */
    public function normalizedRole(): string
    {
        return str_replace(' ', '_', strtolower((string) $this->role_name));
    }

    /**
     * The role this user is currently acting as. Lead Broker can toggle between
     * an admin-like and broker-like view via session('acting_as').
     */
    public function actingRole(): string
    {
        $role = $this->normalizedRole();

        if ($role === 'lead_broker') {
            return session('acting_as', 'broker');
        }

        return $role;
    }

    /**
     * Get the team memberships for this user.
     */
    public function teamMemberships()
    {
        return $this->hasMany(TeamMember::class, 'user_id', 'user_id');
    }

    /**
     * Get the properties assigned to this user.
     */
    public function properties()
    {
        return $this->hasMany(Property::class, 'user_id', 'user_id');
    }

    /**
     * Get the reviews for this user.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id', 'user_id');
    }

    public function writtenReviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id', 'user_id');
    }

    public function receivedReviews()
    {
        return $this->hasMany(Review::class, 'reviewed_user_id', 'user_id');
    }

    public function conversationParticipants()
    {
        return $this->hasMany(ConversationParticipant::class, 'user_id', 'user_id');
    }

    /**
     * Staff roles that maintain a professional profile (license, bio, credentials).
     */
    public function isProfessional(): bool
    {
        return in_array($this->role_name, ['Lead Broker', 'Broker', 'Agent'], true);
    }

    /**
     * Appointments assigned to this user as the listing professional.
     */
    public function appointments()
    {
        return $this->hasManyThrough(
            Appointment::class,
            PropertyAssign::class,
            'user_id',
            'assign_id',
            'user_id',
            'assign_id'
        );
    }

    /**
     * Viewing requests this client submitted (matched by contact email).
     */
    public function bookedAppointments()
    {
        return $this->hasMany(Appointment::class, 'client_id', 'user_id');
    }

    /**
     * Get the blocked dates for this user.
     */
    public function blockedDates()
    {
        return $this->hasMany(BlockedDate::class, 'user_id', 'user_id');
    }
}