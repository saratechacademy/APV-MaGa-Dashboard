<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Compte en attente de validation
            if ($user->status === 'pending') {
                Auth::logout();
                return redirect()->route('login')
                    ->withErrors(['email' => 'Votre compte est en attente de validation par un administrateur.']);
            }

            // Compte suspendu
            if ($user->status === 'suspended') {
                Auth::logout();
                return redirect()->route('login')
                    ->withErrors(['email' => 'Votre compte a été suspendu. Contactez l\'administrateur.']);
            }
        }

        return $next($request);
    }
}