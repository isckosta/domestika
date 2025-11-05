<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Professional extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'bio',
        'skills',
        'photo',
        'reputation_score',
        'reputation_badges',
        'schedule',
        'embedding_profile',
        'total_reviews',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'reputation_badges' => 'array',
            'schedule' => 'array',
            'reputation_score' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Professional {$eventName}");
    }

    /**
     * Get the user that owns this professional profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all responses from this professional.
     */
    public function responses(): HasMany
    {
        return $this->hasMany(ProfessionalResponse::class);
    }

    /**
     * Get all reviews for this professional.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}

