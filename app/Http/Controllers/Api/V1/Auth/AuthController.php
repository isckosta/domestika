<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User registered successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object"),
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign default role (contractor) - user can upgrade to professional later
        $user->assignRole('contractor');

        // Send email verification notification
        $user->sendEmailVerificationNotification();

        $token = JWTAuth::fromUser($user);

        // Log registration
        Log::info('User registered', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return $this->success([
            'user' => $user->load('roles'),
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'email_verification_required' => true,
        ], 'User registered successfully. Please verify your email.', 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Check for too many failed attempts
        $failedAttempts = LoginAttempt::getFailedAttemptsCountForEmail($request->email, 15);
        if ($failedAttempts >= 5) {
            LoginAttempt::recordFailure($request->email, $ipAddress, $userAgent);

            return $this->error(
                'Too many failed attempts',
                'Too many failed login attempts. Please try again in 15 minutes.',
                429
            );
        }

        if (!$token = JWTAuth::attempt($credentials)) {
            // Record failed attempt
            LoginAttempt::recordFailure($request->email, $ipAddress, $userAgent);

            Log::warning('Failed login attempt', [
                'email' => $request->email,
                'ip_address' => $ipAddress,
            ]);

            return $this->error(
                'Unauthorized',
                'Invalid credentials',
                401
            );
        }

        $user = auth()->user();

        // Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            // Record failed attempt (email not verified)
            LoginAttempt::recordFailure($request->email, $ipAddress, $userAgent);

            return $this->error(
                'Email not verified',
                'Please verify your email address before logging in.',
                403
            );
        }

        // Record successful attempt
        LoginAttempt::recordSuccess($user, $ipAddress, $userAgent);

        // Log successful login
        Log::info('User logged in', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ipAddress,
        ]);

        return $this->respondWithToken($token);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $user = auth()->user();
        $token = JWTAuth::getToken();

        JWTAuth::invalidate($token);

        // Log logout
        Log::info('User logged out', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return $this->success(null, 'Successfully logged out');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     tags={"Authentication"},
     *     summary="Refresh JWT token",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Token refreshed successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string"),
     *                 @OA\Property(property="token_type", type="string", example="bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        $currentToken = JWTAuth::getToken();
        $newToken = JWTAuth::refresh($currentToken);

        // Invalidate old token (refresh token rotation)
        try {
            JWTAuth::invalidate($currentToken);
        } catch (\Exception $e) {
            // Token might already be invalidated, log but continue
            Log::warning('Failed to invalidate old token during refresh', [
                'error' => $e->getMessage(),
            ]);
        }

        // Log token refresh
        Log::info('Token refreshed', [
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
        ]);

        return $this->respondWithToken($newToken, 'Token refreshed successfully');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     tags={"Authentication"},
     *     summary="Get authenticated user",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User details retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(): JsonResponse
    {
        $user = auth()->user()->load(['roles', 'professional']);

        return $this->success([
            'user' => new \App\Http\Resources\UserResource($user),
            'has_professional_profile' => !is_null($user->professional),
            'professional_profile' => $user->professional
                ? new \App\Http\Resources\ProfessionalResource($user->professional)
                : null,
        ], 'User details retrieved successfully');
    }

    /**
     * Verify user's email address.
     *
     * @OA\Get(
     *     path="/api/v1/auth/email/verify/{id}/{hash}",
     *     tags={"Authentication"},
     *     summary="Verify email address",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="hash", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Email verified successfully"),
     *     @OA\Response(response=403, description="Invalid verification link")
     * )
     */
    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->error(
                'Invalid verification link',
                'The verification link is invalid.',
                403
            );
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success(null, 'Email already verified');
        }

        if ($user->markEmailAsVerified()) {
            Log::info('Email verified', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
            ]);
        }

        return $this->success(null, 'Email verified successfully');
    }

    /**
     * Resend email verification notification.
     *
     * @OA\Post(
     *     path="/api/v1/auth/email/resend",
     *     tags={"Authentication"},
     *     summary="Resend email verification",
     *     security={{"bearer_token":{}}},
     *     @OA\Response(response=200, description="Verification email sent"),
     *     @OA\Response(response=403, description="Email already verified")
     * )
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->error(
                'Email already verified',
                'Your email address is already verified.',
                403
            );
        }

        $user->sendEmailVerificationNotification();

        Log::info('Email verification resent', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $this->success(null, 'Verification email sent successfully');
    }

    /**
     * Send password reset link.
     *
     * @OA\Post(
     *     path="/api/v1/auth/password/forgot",
     *     tags={"Authentication"},
     *     summary="Request password reset",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset link sent"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            Log::info('Password reset requested', [
                'email' => $request->email,
                'ip_address' => $request->ip(),
            ]);

            return $this->success(null, 'Password reset link sent to your email');
        }

        return $this->error(
            'Failed to send reset link',
            'We could not send a password reset link. Please try again later.',
            500
        );
    }

    /**
     * Validate password reset token.
     *
     * @OA\Get(
     *     path="/api/v1/auth/password/reset",
     *     tags={"Authentication"},
     *     summary="Validate password reset token",
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="email")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token is valid"
     *     ),
     *     @OA\Response(response=422, description="Invalid or expired token")
     * )
     */
    public function validateResetToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error(
                'Invalid token',
                'Invalid or expired reset token.',
                422
            );
        }

        // Check if there's a valid password reset token for this user
        // The actual token validation happens in resetPassword, but we can check if one exists
        $hasToken = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->where('created_at', '>', now()->subHours(1)) // Token expires in 1 hour
            ->exists();

        if (!$hasToken) {
            return $this->error(
                'Invalid token',
                'Invalid or expired reset token.',
                422
            );
        }

        // Return success - token appears to be valid
        // Note: Full validation happens in resetPassword endpoint
        return $this->success([
            'email' => $user->email,
            'token' => $request->token,
            'valid' => true,
            'message' => 'Token appears valid. Use POST /auth/password/reset to reset your password.',
        ], 'Token is valid. You can proceed with password reset.');
    }

    /**
     * Reset user password.
     *
     * @OA\Post(
     *     path="/api/v1/auth/password/reset",
     *     tags={"Authentication"},
     *     summary="Reset password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "token", "password", "password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="password_confirmation", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            Log::info('Password reset completed', [
                'email' => $request->email,
                'ip_address' => $request->ip(),
            ]);

            return $this->success(null, 'Password reset successfully');
        }

        return $this->error(
            'Password reset failed',
            'Invalid or expired reset token.',
            422
        );
    }

    /**
     * Get the token array structure.
     */
    protected function respondWithToken(string $token, string $message = 'Login successful'): JsonResponse
    {
        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], $message);
    }
}
