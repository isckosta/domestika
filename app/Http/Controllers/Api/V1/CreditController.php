<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Credit\AddCreditsRequest;
use App\Http\Requests\Credit\DeductCreditsRequest;
use App\Http\Requests\Credit\TransferCreditsRequest;
use App\Http\Resources\CreditBalanceResource;
use App\Http\Resources\CreditTransactionResource;
use App\Models\User;
use App\Models\UserCredit;
use App\Services\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Credits",
 *     description="Credit system endpoints for managing D$ (Domestika Credits)"
 * )
 */
class CreditController extends BaseController
{
    protected CreditService $creditService;

    public function __construct(CreditService $creditService)
    {
        $this->creditService = $creditService;
    }

    /**
     * Get current user's credit balance.
     *
     * @OA\Get(
     *     path="/api/v1/credits/balance",
     *     summary="Get credit balance",
     *     tags={"Credits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Credit balance retrieved successfully"
     *     )
     * )
     */
    public function balance(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $userCredit = $user->creditAccount ?? new UserCredit();
            $this->authorize('viewBalance', $userCredit);
            
            $balance = $this->creditService->getBalance($user);

            return $this->success([
                'balance' => $balance,
                'user_id' => $user->id,
            ], 'Balance retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve balance', $e->getMessage(), 500);
        }
    }

    /**
     * Add credits to a user account (Admin only).
     *
     * @OA\Post(
     *     path="/api/v1/credits/add",
     *     summary="Add credits to user",
     *     tags={"Credits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "amount", "reason"},
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="amount", type="integer", minimum=1),
     *             @OA\Property(property="reason", type="string"),
     *             @OA\Property(property="reference_id", type="string"),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Credits added successfully"
     *     ),
     *     @OA\Response(response=403, description="Forbidden - Admin role required")
     * )
     */
    public function add(AddCreditsRequest $request): JsonResponse
    {
        try {
            $user = User::findOrFail($request->user_id);
            $userCredit = $user->creditAccount ?? new UserCredit();
            $this->authorize('addCredits', $userCredit);

            $transaction = $this->creditService->addCredits(
                $user,
                $request->amount,
                $request->reason,
                $request->reference_id,
                $request->metadata
            );

            $balance = $this->creditService->getBalance($user);

            return $this->success([
                'transaction' => new CreditTransactionResource($transaction),
                'balance' => $balance,
            ], 'Credits added successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to add credits', $e->getMessage(), 500);
        }
    }

    /**
     * Deduct credits from current user's account.
     *
     * @OA\Post(
     *     path="/api/v1/credits/deduct",
     *     summary="Deduct credits from user",
     *     tags={"Credits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "reason"},
     *             @OA\Property(property="amount", type="integer", minimum=1),
     *             @OA\Property(property="reason", type="string"),
     *             @OA\Property(property="reference_id", type="string"),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Credits deducted successfully"
     *     )
     * )
     */
    public function deduct(DeductCreditsRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $userCredit = $user->creditAccount ?? new UserCredit();
            $this->authorize('deductCredits', $userCredit);

            $transaction = $this->creditService->deductCredits(
                $user,
                $request->amount,
                $request->reason,
                $request->reference_id,
                $request->metadata
            );

            $balance = $this->creditService->getBalance($user);

            return $this->success([
                'transaction' => new CreditTransactionResource($transaction),
                'balance' => $balance,
            ], 'Credits deducted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to deduct credits', $e->getMessage(), 500);
        }
    }

    /**
     * Transfer credits to another user.
     *
     * @OA\Post(
     *     path="/api/v1/credits/transfer",
     *     summary="Transfer credits to another user",
     *     tags={"Credits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"to_user_id", "amount", "reason"},
     *             @OA\Property(property="to_user_id", type="integer"),
     *             @OA\Property(property="amount", type="integer", minimum=1),
     *             @OA\Property(property="reason", type="string"),
     *             @OA\Property(property="reference_id", type="string"),
     *             @OA\Property(property="metadata", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Credits transferred successfully"
     *     )
     * )
     */
    public function transfer(TransferCreditsRequest $request): JsonResponse
    {
        try {
            $fromUser = $request->user();
            $toUser = User::findOrFail($request->to_user_id);
            $userCredit = $fromUser->creditAccount ?? new UserCredit();
            $this->authorize('transferCredits', $userCredit);

            $transactions = $this->creditService->transferCredits(
                $fromUser,
                $toUser,
                $request->amount,
                $request->reason,
                $request->reference_id,
                $request->metadata
            );

            $balance = $this->creditService->getBalance($fromUser);

            return $this->success([
                'transaction_out' => new CreditTransactionResource($transactions['transaction_out']),
                'transaction_in' => new CreditTransactionResource($transactions['transaction_in']),
                'balance' => $balance,
            ], 'Credits transferred successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to transfer credits', $e->getMessage(), 500);
        }
    }

    /**
     * Get current user's transaction history.
     *
     * @OA\Get(
     *     path="/api/v1/credits/transactions",
     *     summary="Get transaction history",
     *     tags={"Credits"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of transactions to retrieve",
     *         required=false,
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction history retrieved successfully"
     *     )
     * )
     */
    public function transactions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $userCredit = $user->creditAccount ?? new UserCredit();
            $this->authorize('viewTransactions', $userCredit);
            
            $limit = $request->query('limit', 50);

            $transactions = $this->creditService->getTransactionHistory($user, $limit);

            return $this->success([
                'transactions' => CreditTransactionResource::collection($transactions),
                'count' => $transactions->count(),
            ], 'Transaction history retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve transactions', $e->getMessage(), 500);
        }
    }
}
