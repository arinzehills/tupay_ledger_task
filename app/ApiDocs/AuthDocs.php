<?php

namespace App\ApiDocs;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication endpoints"
 * )
 *
 * @OA\Post(
 *     path="/api/login",
 *     tags={"Auth"},
 *     summary="Login with email and password",
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"email","password"},
 *
 *             @OA\Property(property="email", type="string", example="user@example.com"),
 *             @OA\Property(property="password", type="string", example="password123")
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="token", type="string"),
 *             @OA\Property(property="user", type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="email", type="string"),
 *                 @OA\Property(property="first_name", type="string"),
 *                 @OA\Property(property="last_name", type="string"),
 *                 @OA\Property(property="middle_name", type="string", nullable=true)
 *             ),
 *             @OA\Property(property="wallets", type="array",
 *
 *                 @OA\Items(type="object",
 *
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="currency", type="string", example="NGN"),
 *                     @OA\Property(property="balance", type="integer", example=500000000)
 *                 )
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 *
 * @OA\Post(
 *     path="/api/2fa/challenge",
 *     tags={"Auth"},
 *     summary="Issue Elevated Action Token (EAT) after TOTP verification",
 *     security={{"Bearer":{}}},
 *
 *     @OA\RequestBody(
 *         required=true,
 *
 *         @OA\JsonContent(
 *             required={"totp_code","action_payload"},
 *
 *             @OA\Property(property="totp_code", type="string", example="123456", description="6-digit TOTP code from authenticator app"),
 *             @OA\Property(property="action_payload", type="object", description="Action parameters (must match exactly for swap)",
 *                 @OA\Property(property="source_currency", type="string", example="NGN"),
 *                 @OA\Property(property="destination_currency", type="string", example="CNY"),
 *                 @OA\Property(property="amount", type="integer", example=1000000)
 *             )
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="EAT issued successfully",
 *
 *         @OA\JsonContent(
 *
 *             @OA\Property(property="eat_token", type="string")
 *         )
 *     ),
 *
 *     @OA\Response(response=401, description="Invalid TOTP")
 * )
 */
class AuthDocs {}
