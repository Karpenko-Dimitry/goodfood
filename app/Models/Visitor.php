<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A browser seen on the site (guest, or a user once signed in). Written by VisitorTracker.
 */
class Visitor extends Model
{
    use MassPrunable;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function onlineSince(): Carbon
    {
        return now()->subMinutes(config('visitors.online_minutes'));
    }

    public function scopeOnline(Builder $query): void
    {
        $query->where('last_seen_at', '>=', static::onlineSince());
    }

    public function scopeGuests(Builder $query): void
    {
        $query->whereNull('user_id');
    }

    public function scopeRegistered(Builder $query): void
    {
        $query->whereNotNull('user_id');
    }

    /** First visit on or after the given moment ("new" visitors). */
    public function scopeNewSince(Builder $query, Carbon $since): void
    {
        $query->where('first_seen_at', '>=', $since);
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at?->gte(static::onlineSince()) ?? false;
    }

    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    public function prunable(): Builder
    {
        return static::where('last_seen_at', '<', now()->subDays(config('visitors.retention_days')));
    }
}
