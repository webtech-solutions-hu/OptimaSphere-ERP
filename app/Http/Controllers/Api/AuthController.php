<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Create a new API token.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string',
            'abilities' => 'nullable|array',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        // Check if user is active and approved
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account is not active.',
            ], 403);
        }

        if (!$user->approved_at) {
            return response()->json([
                'message' => 'Your account is pending approval.',
            ], 403);
        }

        $token = $user->createToken(
            $request->device_name,
            $request->abilities ?? ['*'],
            $request->expires_at ? now()->parse($request->expires_at) : null
        );

        return response()->json([
            'message' => 'Token created successfully',
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token revoked successfully',
        ]);
    }

    /**
     * Revoke all tokens for the authenticated user.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'All tokens revoked successfully',
        ]);
    }

    /**
     * Get current user information.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }

    /**
     * List all tokens for the authenticated user.
     */
    public function tokens(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->get()->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'expires_at' => $token->expires_at,
            ];
        });

        return response()->json([
            'data' => $tokens,
        ]);
    }

    /**
     * Revoke a specific token by ID.
     */
    public function revokeToken(Request $request, string $tokenId): JsonResponse
    {
        $deleted = $request->user()->tokens()->where('id', $tokenId)->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Token not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Token revoked successfully',
        ]);
    }
}
