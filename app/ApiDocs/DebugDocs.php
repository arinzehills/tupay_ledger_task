<?php

namespace App\ApiDocs;

/**
 * @OA\Tag(
 *     name="Debug",
 *     description="Debug endpoints (development only, requires APP_DEBUG=true)"
 * )
 *
 * @OA\Get(
 *     path="/api/debug/totp",
 *     tags={"Debug"},
 *     summary="Get current TOTP code for testing",
 *     description="DEVELOPMENT ONLY - Returns the current 6-digit TOTP code for the authenticated user. Only available when APP_DEBUG=true.",
 *     security={{"Bearer":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Current TOTP code",
 *         @OA\JsonContent(
 *             @OA\Property(property="totp_code", type="string", example="123456"),
 *             @OA\Property(property="user_id", type="integer"),
 *             @OA\Property(property="email", type="string"),
 *             @OA\Property(property="valid_for_seconds", type="integer", example=30),
 *             @OA\Property(property="warning", type="string", example="DEBUG ENDPOINT - Only available in development mode")
 *         )
 *     ),
 *     @OA\Response(response=403, description="Debug mode disabled (APP_DEBUG=false)"),
 *     @OA\Response(response=400, description="TOTP not configured for user")
 * )
 */
class DebugDocs
{
}