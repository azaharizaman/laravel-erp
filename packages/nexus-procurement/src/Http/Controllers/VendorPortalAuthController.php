<?php

declare(strict_types=1);

namespace Nexus\Procurement\Http\Controllers;

use Nexus\Procurement\Models\VendorUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Vendor Portal Authentication Controller
 *
 * Handles vendor user authentication for the vendor portal.
 */
class VendorPortalAuthController extends Controller
{
    /**
     * Login vendor user.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = VendorUser::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'error' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->canAccessPortal()) {
            return response()->json([
                'error' => 'Account is inactive or vendor is suspended'
            ], 403);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Create token (assuming Sanctum is used)
        $token = $user->createToken('vendor-portal')->plainTextToken;

        return response()->json([
            'user' => $user->load('vendor'),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout vendor user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('vendor'));
    }

    /**
     * Request password reset.
     */
    public function requestPasswordReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:vendor_users,email',
        ]);

        $user = VendorUser::where('email', $validated['email'])->first();

        if (!$user->canAccessPortal()) {
            return response()->json([
                'error' => 'Account is inactive'
            ], 403);
        }

        // Generate reset token
        $token = Str::random(60);
        $user->update([
            'password_reset_token' => Hash::make($token),
            'password_reset_expires_at' => now()->addHours(24),
        ]);

        // Send reset email (notification would be implemented here)
        // $user->notify(new PasswordResetNotification($token));

        return response()->json([
            'message' => 'Password reset link sent to your email'
        ]);
    }

    /**
     * Reset password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:vendor_users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = VendorUser::where('email', $validated['email'])->first();

        if (!$user || !$user->password_reset_token ||
            !Hash::check($validated['token'], $user->password_reset_token) ||
            $user->password_reset_expires_at < now()) {
            return response()->json([
                'error' => 'Invalid or expired reset token'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
        ]);

        return response()->json([
            'message' => 'Password reset successfully'
        ]);
    }

    /**
     * Create initial vendor user (admin function).
     */
    public function createVendorUser(Request $request): JsonResponse
    {
        // This would typically be called by an admin user
        // For now, basic implementation
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'email' => 'required|email|unique:vendor_users,email',
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'is_primary_contact' => 'boolean',
            'send_invitation' => 'boolean',
        ]);

        $tempPassword = Str::random(12);

        $user = VendorUser::create([
            'vendor_id' => $validated['vendor_id'],
            'email' => $validated['email'],
            'password' => Hash::make($tempPassword),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'] ?? null,
            'is_primary_contact' => $validated['is_primary_contact'] ?? false,
            'is_active' => true,
        ]);

        // Send invitation email with temporary password
        if ($validated['send_invitation'] ?? true) {
            // $user->notify(new VendorInvitationNotification($tempPassword));
        }

        return response()->json([
            'user' => $user,
            'temporary_password' => $tempPassword, // In production, this would only be sent via email
        ], 201);
    }
}