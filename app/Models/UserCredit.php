<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCredit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'balance',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'integer',
    ];

    /**
     * Get the user that owns the credit account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this credit account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Check if user has sufficient balance.
     */
    public function hasSufficientBalance(int $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Increment balance.
     */
    public function incrementBalance(int $amount): void
    {
        $this->increment('balance', $amount);
    }

    /**
     * Decrement balance.
     */
    public function decrementBalance(int $amount): void
    {
        $this->decrement('balance', $amount);
    }
}
