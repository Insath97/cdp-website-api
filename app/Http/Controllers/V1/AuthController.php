<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Models\User;
use App\Traits\ActivityLogTrait;

class AuthController extends Controller
{
    use ActivityLogTrait;

    /** 
     * Admin Login
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $credentials = $request->only('email', 'password');

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                $this->logActivity('FAILED_LOGIN', 'Auth', "Failed login attempt: Email '{$request->email}' not found", [
                    'email' => $request->email,
                    'reason' => 'email_not_found',
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'No account found with this email address.',
                    'errors' => [
                        'email' => ['No account found with this email address.']
                    ]
                ], 401);
            }

            if (!$token = Auth::guard('api')->attempt($credentials)) {
                $this->logActivity('FAILED_LOGIN', 'Auth', "Failed login attempt: Incorrect password for '{$user->email}'", [
                    'email' => $user->email,
                    'reason' => 'invalid_password',
                ], $user->id);

                return response()->json([
                    'status' => 'error',
                    'message' => 'The password you entered is incorrect.',
                    'errors' => [
                        'password' => ['The password you entered is incorrect. Please try again.']
                    ]
                ], 401);
            }

            if (!$user->canLogin()) {
                $this->logActivity('LOGIN_BLOCKED', 'Auth', "Blocked login attempt: Account deactivated for '{$user->email}'", [
                    'email' => $user->email,
                    'reason' => 'account_deactivated',
                ], $user->id);

                Auth::guard('api')->logout();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account is deactivated',
                    'errors' => [
                        'email' => ['Your account has been deactivated. Please contact administrator.']
                    ]
                ], 401);
            }

            if (!$user->roles()->exists()) {
                $this->logActivity('LOGIN_BLOCKED', 'Auth', "Blocked login attempt: No admin role assigned for '{$user->email}'", [
                    'email' => $user->email,
                    'reason' => 'no_role_assigned',
                ], $user->id);

                Auth::guard('api')->logout();
                return response()->json([
                    'status' => 'error',
                    'message' => 'No admin role assigned. Please contact Super Admin.',
                    'errors' => [
                        'email' => ['No admin role assigned. Please contact Super Admin.']
                    ]
                ], 403);
            }

            $user->updateLastLogin($request->ip());

            $this->logActivity('LOGIN', 'Auth', "User {$user->name} ({$user->email}) logged in successfully", [
                'email' => $user->email,
                'user_id' => $user->id,
            ], $user->id);

            $cookie = cookie(
                'auth_token',
                $token,
                60 * 24 * 7,
                '/',
                null,
                true,  // Secure
                true,  // HttpOnly
                false,
                'lax'
            );

            $user->load(['roles' => function ($query) {
                $query->select('id', 'name')
                    ->with(['permissions' => function ($query) {
                        $query->select('id', 'name');
                    }]);
            }]);

            if ($user->relationLoaded('roles')) {
                $user->roles->each->makeHidden(['pivot']);
                $user->roles->each(function ($role) {
                    if ($role->relationLoaded('permissions')) {
                        $role->permissions->each->makeHidden(['pivot']);
                    }
                });
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'auth_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60
                ]
            ], 200)->cookie($cookie);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to login',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Admin Logout
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::guard('api')->user();
            $userId = $user ? $user->id : null;
            $userName = $user ? $user->name : 'Unknown';

            // Logout the user (invalidates the token)
            Auth::guard('api')->logout();

            if ($userId) {
                $this->logActivity('LOGOUT', 'Auth', "User {$userName} logged out successfully", [
                    'user_id' => $userId,
                ], $userId);
            }

            // Create an expired cookie to remove it from browser
            $cookie = Cookie::forget('auth_token');

            return response()->json([
                'status' => 'success',
                'message' => 'Logout successful'
            ], 200)->withCookie($cookie);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to logout',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get authenticated admin user
     */
    public function me()
    {
        try {
            $user = auth('api')->user();

            $user->load(['roles' => function ($query) {
                $query->select('id', 'name')
                    ->with(['permissions' => function ($query) {
                        $query->select('id', 'name');
                    }]);
            }]);

            if ($user->relationLoaded('roles')) {
                $user->roles->each->makeHidden(['pivot']);
                $user->roles->each(function ($role) {
                    if ($role->relationLoaded('permissions')) {
                        $role->permissions->each->makeHidden(['pivot']);
                    }
                });
            }

            return response()->json([
                'status' => 'success',
                'message' => 'User details fetched successfully',
                'data' => [
                    'user' => $user
                ]
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user details',
                'error' => config('app.debug') ? $th->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
