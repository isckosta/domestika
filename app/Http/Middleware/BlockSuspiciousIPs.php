<?php

namespace App\Http\Middleware;

use App\Models\LoginAttempt;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class BlockSuspiciousIPs
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ipAddress = $request->ip();
        $blockKey = "blocked_ip:{$ipAddress}";
        $attemptsKey = "login_attempts:{$ipAddress}";

        // Check if IP is blocked
        if (Redis::exists($blockKey)) {
            $blockedUntil = Redis::get($blockKey);
            
            if (now()->timestamp < $blockedUntil) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'IP address temporarily blocked due to suspicious activity',
                    'error' => 'IP_BLOCKED',
                    'retry_after' => $blockedUntil - now()->timestamp,
                ], 429);
            } else {
                // Block expired, remove it
                Redis::del($blockKey);
                Redis::del($attemptsKey);
            }
        }

        // Check failed attempts
        $failedAttempts = LoginAttempt::getFailedAttemptsCount($ipAddress, 15);

        if ($failedAttempts >= 5) {
            // Block IP for 15 minutes
            $blockUntil = now()->addMinutes(15)->timestamp;
            Redis::setex($blockKey, 900, $blockUntil); // 15 minutes in seconds

            return response()->json([
                'status' => 'error',
                'message' => 'Too many failed login attempts. IP address blocked for 15 minutes',
                'error' => 'IP_BLOCKED',
                'retry_after' => 900,
            ], 429);
        }

        return $next($request);
    }
}

