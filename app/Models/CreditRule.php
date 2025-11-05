<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CreditRule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event',
        'amount',
        'description',
        'is_active',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Event types for credit rewards.
     */
    public const EVENT_POSITIVE_REVIEW = 'positive_review';
    public const EVENT_SERVICE_COMPLETED = 'service_completed';
    public const EVENT_QUICK_RESPONSE = 'quick_response';
    public const EVENT_ACCOUNT_VERIFIED = 'account_verified';
    public const EVENT_PROFILE_COMPLETED = 'profile_completed';
    public const EVENT_FIRST_SERVICE = 'first_service';
    public const EVENT_REFERRAL = 'referral';

    /**
     * Scope a query to only include active rules.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Find active rule by event.
     */
    public static function findByEvent(string $event): ?self
    {
        return self::where('event', $event)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all active rules.
     */
    public static function getActiveRules(): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()->get();
    }
}
