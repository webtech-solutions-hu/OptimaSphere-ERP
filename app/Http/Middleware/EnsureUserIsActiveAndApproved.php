<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActiveAndApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && !$user->canAccessSystem()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $loginUrl = Filament::getLoginUrl();

            if (!$user->isEmailVerified()) {
                return redirect($loginUrl)
                    ->with('error', 'Please verify your email address before accessing the system. Check your inbox for the verification link.');
            }

            if (!$user->is_active) {
                return redirect($loginUrl)
                    ->with('error', 'Your account is not active. Please contact an administrator.');
            }

            if (!$user->isApproved()) {
                return redirect($loginUrl)
                    ->with('error', 'Your account is pending approval. Please wait for an administrator to approve your account.');
            }
        }

        return $next($request);
    }
}
