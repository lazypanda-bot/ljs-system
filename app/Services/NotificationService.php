<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Appointment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Admin oversight feed: a copy of a system event for every Admin and Lead Broker
     * (audience "system"). Lead Brokers only see these while acting as admin.
     *
     * @param  array<string, mixed>  $attributes  type, title, body, url, appointment_id...
     * @param  iterable<User|null>  $personalRecipients  users who already got a personal copy
     */
    public static function oversight(array $attributes, ?User $actor = null, iterable $personalRecipients = []): void
    {
        $alreadyNotifiedAdmins = collect($personalRecipients)
            ->filter(fn ($user) => $user instanceof User && $user->role_name === 'Admin')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id);

        User::query()
            ->where('account_status', 'Active')
            ->whereIn('role_name', ['Admin', 'Lead Broker'])
            ->when($actor, fn ($query) => $query->whereKeyNot($actor->user_id))
            ->when($alreadyNotifiedAdmins->isNotEmpty(), fn ($query) => $query->whereNotIn('user_id', $alreadyNotifiedAdmins->all()))
            ->get()
            ->each(fn (User $user) => AppNotification::create([
                ...$attributes,
                'user_id' => $user->user_id,
                'audience' => 'system',
            ]));
    }

    /** Ask a client to review the professional they completed an appointment with. */
    public static function reviewPrompt(?User $client, ?User $professional, string $context): void
    {
        if (! $client || ! $professional || $client->normalizedRole() !== 'client'
            || ! in_array($professional->role_name, ['Broker', 'Agent', 'Lead Broker'], true)) {
            return;
        }

        $url = route('agents.show', $professional) . '#reviews';
        $alreadyAsked = AppNotification::query()
            ->where('user_id', $client->user_id)
            ->where('title', 'How was your experience?')
            ->where('url', $url)
            ->whereNull('read_at')
            ->exists();
        if ($alreadyAsked) {
            return;
        }

        AppNotification::create([
            'user_id' => $client->user_id,
            'type' => 'listing',
            'title' => 'How was your experience?',
            'body' => "Please rate {$professional->name} after {$context}.",
            'url' => $url,
        ]);
    }

    /** A closed deal: prompt every client who completed an appointment on the property. */
    public static function soldReviewPrompts(Property $property): void
    {
        Appointment::query()
            ->with(['client', 'assignment.user'])
            ->where('appointment_status', 'Completed')
            ->whereHas('assignment', fn ($query) => $query->where('property_id', $property->property_id))
            ->get()
            ->each(function (Appointment $appointment) use ($property) {
                $client = $appointment->client
                    ?? User::where('email', $appointment->contact_email)->where('role_name', 'Client')->first();
                self::reviewPrompt($client, $appointment->assignment?->user, "the sale of {$property->property_name}");
            });
    }

    /**
     * Tell every active client (bell icon) and every active professional that a listing is live.
     * The lister and the acting user are skipped; admins receive the oversight copy instead.
     */
    public static function listingLive(Property $property, ?User $actor = null): void
    {
        $skip = collect([$actor?->user_id, $property->user_id])->filter()->map(fn ($id) => (int) $id)->all();
        $now = now();
        $title = 'New property listing';
        $body = $property->property_name . ' is now available.';

        User::query()
            ->where('account_status', 'Active')
            ->whereIn('role_name', ['Client', 'Broker', 'Agent', 'Lead Broker'])
            ->when($skip !== [], fn ($query) => $query->whereNotIn('user_id', $skip))
            ->select(['user_id', 'role_name'])
            ->chunkById(500, function ($users) use ($property, $now, $title, $body): void {
                $rows = $users->map(fn (User $user) => [
                    'user_id' => $user->user_id,
                    'type' => 'listing',
                    'audience' => 'personal',
                    'title' => $title,
                    'body' => $body,
                    'url' => $user->role_name === 'Client'
                        ? route('properties.show', $property)
                        : route('staff.properties.show', $property),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('app_notifications')->insert($rows);
            }, 'user_id');

        self::oversight([
            'type' => 'listing',
            'title' => $title,
            'body' => $body,
            'url' => route('staff.properties.show', $property),
        ], $actor);
    }
}
