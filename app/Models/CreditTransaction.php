<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'reason',
        'reference_id',
        'related_user_id',
        'transaction_hash',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Transaction types.
     */
    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related user (for transfers).
     */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    /**
     * Generate transaction hash for audit trail.
     */
    public static function generateHash(
        string $userId,
        int $amount,
        string $type,
        string $reason,
        ?string $referenceId = null,
        ?string $relatedUserId = null
    ): string {
        $data = [
            'user_id' => $userId,
            'amount' => $amount,
            'type' => $type,
            'reason' => $reason,
            'reference_id' => $referenceId,
            'related_user_id' => $relatedUserId,
            'timestamp' => now()->toIso8601String(),
        ];

        return hash('sha256', json_encode($data));
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_hash)) {
                $transaction->transaction_hash = self::generateHash(
                    $transaction->user_id,
                    $transaction->amount,
                    $transaction->type,
                    $transaction->reason,
                    $transaction->reference_id,
                    $transaction->related_user_id
                );
            }
        });
    }
}
