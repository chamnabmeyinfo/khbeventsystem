<?php

namespace AppHttpMiddleware;

use Closure;
use IlluminateHttpRequest;
use IlluminateSupportFacadesAuth;
use SymfonyComponentHttpFoundationResponse;

class EnsureStaffIsActive
{
    /**
     * Handle an incoming request.
     * Ensure that if any staff or user is inactive or terminated,
     * they are strictly prohibited from accessing any part of the system.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (! $user->isActive()) {
                $isTerminated = method_exists($user, 'isTerminated') && $user->isTerminated();

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $message = $isTerminated
                    ? 'This account has been terminated. You are strictly prohibited from accessing the system anywhere.'
                    : 'Your account is inactive or suspended. Access anywhere in the system is not permitted.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 403,
                        'message' => $message,
                    ], 403);
                }

                return redirect()->route('login')->withErrors(['username' => $message]);
            }
        }

        return $next($request);
    }
}
