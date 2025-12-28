<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Staf;
use App\Models\Login;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SSOController extends Controller
{
    /**
     * Generate SSO token for external applications
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function generateToken(Request $request): JsonResponse
    {
        $request->validate([
            'no_kp' => 'required|string|size:12',
            'password' => 'required|string',
            'device_name' => 'string|nullable',
        ]);

        // Clean IC number
        $noKp = preg_replace('/[^0-9]/', '', $request->no_kp);

        // Find staff
        $staf = Staf::where('no_kp', $noKp)->first();

        if (!$staf || !Hash::check($request->password, $staf->password ?? '')) {
            throw ValidationException::withMessages([
                'no_kp' => ['Maklumat tidak tepat.'],
            ]);
        }

        // Check if staff is active
        if (!$staf->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Akaun anda tidak aktif. Sila hubungi admin.',
                'message_en' => 'Your account is inactive. Please contact admin.',
            ], 403);
        }

        // Create token
        $deviceName = $request->device_name ?? $request->userAgent();
        $token = $staf->createToken($deviceName)->plainTextToken;

        // Log the login
        Login::create([
            'id_staf' => $staf->id_staf,
            'waktu_login' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
        ]);

        return response()->json([
            'success' => true,
            'token' => $token,
            'token_type' => 'Bearer',
            'staf' => [
                'id_staf' => $staf->id_staf,
                'no_staf' => $staf->no_staf,
                'no_kp' => $staf->no_kp,
                'nama' => $staf->nama,
                'emel' => $staf->emel,
                'gambar_url' => $staf->gambar_url,
            ],
            'message' => 'Token SSO berjaya dijana.',
            'message_en' => 'SSO token generated successfully.',
        ]);
    }

    /**
     * Verify SSO token
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyToken(Request $request): JsonResponse
    {
        $staf = $request->user();

        if (!$staf) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak sah.',
                'message_en' => 'Invalid token.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'valid' => true,
            'staf' => [
                'id_staf' => $staf->id_staf,
                'no_staf' => $staf->no_staf,
                'no_kp' => $staf->no_kp,
                'nama' => $staf->nama,
                'emel' => $staf->emel,
                'telefon' => $staf->telefon,
                'gambar_url' => $staf->gambar_url,
                'jawatan' => $staf->jawatan?->jawatan,
                'gred' => $staf->gred?->gred,
                'bahagian' => $staf->bahagian?->bahagian,
                'is_active' => $staf->isActive(),
            ],
            'message' => 'Token sah.',
            'message_en' => 'Token is valid.',
        ]);
    }

    /**
     * Revoke SSO token (logout)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function revokeToken(Request $request): JsonResponse
    {
        $staf = $request->user();

        if ($staf) {
            // Revoke current token only
            $request->user()->currentAccessToken()->delete();

            // Log the logout
            Login::where('id_staf', $staf->id_staf)
                ->whereNull('waktu_logout')
                ->latest('waktu_login')
                ->first()
                ?->update(['waktu_logout' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token telah dibatalkan.',
            'message_en' => 'Token has been revoked.',
        ]);
    }

    /**
     * Revoke all tokens for a user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function revokeAllTokens(Request $request): JsonResponse
    {
        $staf = $request->user();

        if ($staf) {
            // Revoke all tokens
            $staf->tokens()->delete();

            // Log all active sessions as logged out
            Login::where('id_staf', $staf->id_staf)
                ->whereNull('waktu_logout')
                ->update(['waktu_logout' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Semua token telah dibatalkan.',
            'message_en' => 'All tokens have been revoked.',
        ]);
    }

    /**
     * Get active tokens for current user
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getActiveTokens(Request $request): JsonResponse
    {
        $staf = $request->user();

        if (!$staf) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $tokens = $staf->tokens()->get()->map(function($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'tokens' => $tokens,
            'count' => $tokens->count(),
        ]);
    }
}

