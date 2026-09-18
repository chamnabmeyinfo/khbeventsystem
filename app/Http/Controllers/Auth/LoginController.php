<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\DebugLogger;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        // #region agent log
        DebugLogger::log(
            [
                'session_id' => session()->getId(),
                'csrf_token' => csrf_token(),
                'session_token' => session()->token(),
            ],
            'LoginController.php:16',
            'Login form shown'
        );
        // #endregion

        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        // #region agent log
        DebugLogger::log(
            [
                'username' => $request->username,
                'session_id' => $request->session()->getId(),
                'csrf_token_before' => csrf_token(),
            ],
            'LoginController.php:28',
            'Login attempt started'
        );
        // #endregion

        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Load user with role, permissions, and employee to prevent N+1 queries
        $user = User::with(['role.permissions', 'employee'])->where('username', $request->username)->first();

        if (! $user) {
            return back()->withErrors([
                'username' => 'Invalid credentials.',
            ])->withInput();
        }

        if ($user->isTerminated()) {
            return back()->withErrors([
                'username' => 'This account has been terminated. Access to the system is strictly prohibited.',
            ])->withInput();
        }

        if (! $user->isActive()) {
            return back()->withErrors([
                'username' => 'This account is inactive or suspended. Access to the system is not permitted.',
            ])->withInput();
        }

        // Manually verify password since we're using username instead of email
        if (Hash::check($request->password, $user->password)) {
            // #region agent log
            DebugLogger::log(
                [
                    'session_id_before' => $request->session()->getId(),
                    'csrf_before' => csrf_token(),
                ],
                'LoginController.php:54',
                'Password verified, before Auth::login'
            );
            // #endregion

            // Login without remember token (column doesn't exist in database)
            Auth::login($user, false);

            // #region agent log
            DebugLogger::log(
                [
                    'session_id_after_login' => $request->session()->getId(),
                    'csrf_after_login' => csrf_token(),
                ],
                'LoginController.php:60',
                'After Auth::login, before regenerate'
            );
            // #endregion

            // Note: last_login update removed as column doesn't exist in actual database

            // Regenerate session and CSRF token
            $request->session()->regenerate();

            // #region agent log
            DebugLogger::log(
                [
                    'session_id_after_regenerate' => $request->session()->getId(),
                    'csrf_after_regenerate' => csrf_token(),
                ],
                'LoginController.php:69',
                'After regenerate, before regenerateToken'
            );
            // #endregion

            $request->session()->regenerateToken();

            // #region agent log
            DebugLogger::log(
                [
                    'session_id_final' => $request->session()->getId(),
                    'csrf_final' => csrf_token(),
                    'session_data' => session()->all(),
                    'intended_url' => '/dashboard',
                ],
                'LoginController.php:75',
                'After regenerateToken, before redirect'
            );
            // #endregion

            $redirect = redirect()->intended('/dashboard');

            // #region agent log
            DebugLogger::log(
                [
                    'redirect_url' => $redirect->getTargetUrl(),
                    'status_code' => $redirect->getStatusCode(),
                ],
                'LoginController.php:81',
                'Redirect created, returning'
            );
            // #endregion

            return $redirect;
        }

        return back()->withErrors([
            'username' => 'Invalid credentials.',
        ])->withInput();
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
