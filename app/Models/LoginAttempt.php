<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAttempt extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'successful',
        'attempted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'attempted_at' => 'datetime',
        ];
    }

    /**
     * Get the user associated with this login attempt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a successful login attempt.
     */
    public static function recordSuccess(User $user, string $ipAddress, ?string $userAgent = null): self
    {
        return self::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'successful' => true,
            'attempted_at' => now(),
        ]);
    }

    /**
     * Record a failed login attempt.
     */
    public static function recordFailure(string $email, string $ipAddress, ?string $userAgent = null): self
    {
        return self::create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'successful' => false,
            'attempted_at' => now(),
        ]);
    }

    /**
     * Get failed attempts count for an IP in the last N minutes.
     */
    public static function getFailedAttemptsCount(string $ipAddress, int $minutes = 15): int
    {
        return self::where('ip_address', $ipAddress)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Get failed attempts count for an email in the last N minutes.
     */
    public static function getFailedAttemptsCountForEmail(string $email, int $minutes = 15): int
    {
        return self::where('email', $email)
            ->where('successful', false)
            ->where('attempted_at', '>=', now()->subMinutes($minutes))
            ->count();
    }
}

